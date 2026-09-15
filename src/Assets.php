<?php

namespace EventEspresso\CalendarPlus;

use WP_Dependencies;
use WP_Scripts;
use WP_Styles;
use _WP_Dependency;

class Assets extends CalendarPlusModule
{
    private const PATH         = 'src/assets';

    private const FILE_EXT_CSS = '.css';

    private const FILE_EXT_JS  = '.js';

    private const FILE_EXT_PHP = '.php';

    public const HANDLE_ADMIN  = 'calendarPlusAdmin';

    public const HANDLE_PUBLIC = 'calendarPlus';

    private string $barista_dir;

    private string $barista_url;

    private array $assets = [
        'css' => [],
        'js'  => [],
    ];

    private array $entry_points = [];

    private array $manifest = [];


    /**
     * @param string $plugin_slug
     * @param string $version
     */
    public function __construct(string $plugin_slug, string $version)
    {
        parent::__construct($plugin_slug, $version);
        $this->barista_dir = defined('EE_BARISTA_DIR') ? EE_BARISTA_DIR : '';
        $this->barista_url = defined('EE_BARISTA_URL') ? EE_BARISTA_URL : '';
    }


    public function registerHooks(): void
    {
        if ($this->isWordPressThemesAdmin()) {
            return;
        }
        // Register immediately if the registry already fired, else hook — another plugin may create it before us.
        if (did_action('wp_default_scripts')) {
            $this->registerScripts(wp_scripts());
        } else {
            add_action('wp_default_scripts', [$this, 'registerScripts']);
        }
        if (did_action('wp_default_styles')) {
            $this->registerStyles(wp_styles());
        } else {
            add_action('wp_default_styles', [$this, 'registerStyles']);
        }
        add_action('admin_enqueue_scripts', [$this, 'registerDependencies'], 0);
        add_action('wp_enqueue_scripts', [$this, 'registerDependencies'], 0);
    }


    private function isWordPressThemesAdmin(): bool
    {
        $request_uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL);
        if (! $request_uri) {
            $request_uri = '';
        }
        $sanitized_uri = esc_url_raw($request_uri);
        return strpos($sanitized_uri, 'wp-admin/themes.php') !== false;
    }


    /**
     * Retrieves a URL to a file in the ee_barista plugin.
     *
     * @param string $path Relative path of the desired file.
     *
     * @return string       Fully qualified URL pointing to the desired file.
     */
    public function url(string $path): string
    {
        return $this->barista_url ? $this->barista_url . $path : EVENTS_CALENDAR_PLUS_BASE_URL . $path;
    }


    /**
     * @param string $asset_filename
     * @return string
     */
    protected function isCalendarPlusAsset(string $asset_filename): string
    {
        return strpos($asset_filename, Assets::HANDLE_PUBLIC) === 0;
    }


    /**
     * @param string $asset
     * @return string
     */
    protected function assetHandle(string $asset): string
    {
        return $this->isCalendarPlusAsset($asset) ? $asset : 'eventespresso-' . $asset;
    }


    /**
     * @return string
     */
    protected function assetsPath(): string
    {
        return $this->barista_dir ? 'build' : Assets::PATH;
    }


    /**
     * @return string
     */
    protected function assetsPathBase(): string
    {
        return $this->barista_dir ?: EVENTS_CALENDAR_PLUS_BASE_PATH;
    }


    /**
     * @return array
     */
    protected function getEntryPoints(): array
    {
        if (! $this->entry_points) {
            $this->entry_points = array_keys($this->getManifest('entrypoints'));
        }
        return $this->entry_points;
    }


    /**
     * @param string $key
     * @return array
     */
    protected function getManifest(string $key = 'files'): array
    {
        if (! $this->manifest) {
            $manifest_path = $this->assetsPathBase() . $this->assetsPath() . '/asset-manifest.json';

            if (! file_exists($manifest_path)) {
                wp_die('No manifest file found! Try running `yarn build` in a terminal');
            }
            $this->manifest = wp_json_file_decode($manifest_path, ['associative' => true]);
        }

        if (! isset($this->manifest[ $key ])) {
            wp_die(sprintf('No entry for %1$s found in manifest file.', esc_html($key)));
        }

        return $this->manifest[ $key ];
    }


    /**
     * @param WP_Dependencies  $assets
     * @param string           $handle
     * @param string           $asset_path
     * @param string[]         $deps
     * @param string|bool|null $ver
     * @param array|string     $args
     * @return _WP_Dependency|null
     * @since 1.0.4
     */
    private function addOrUpdateAsset(
        WP_Dependencies $assets,
        string $handle,
        string $asset_path,
        array $deps,
        $ver,
        $args = null
    ): ?_WP_Dependency {
        if (! file_exists($this->assetsPathBase() . $asset_path)) {
            error_log(
                sprintf(
                    'Asset %s with file path %s does not exist. Verify that the file exists.',
                    $handle,
                    $asset_path
                )
            );
            return null;
        }
        $src   = $this->url($asset_path);
        $asset = $assets->query($handle);
        if ($asset instanceof _WP_Dependency) {
            $asset->src  = $src;
            $asset->deps = $deps;
            $asset->ver  = $ver;
            $asset->args = $args;
        } else {
            $assets->add($handle, $src, $deps, $ver, $args);
            $asset = $assets->query($handle);
        }
        if ($asset instanceof _WP_Dependency) {
            if (isset($args['in_footer']) && $args['in_footer']) {
                /*
                * The script's `group` designation is an indication of whether it is
                * to be printed in the header or footer. The behaviour here defers to
                * the arguments as passed. Specifically, group data is not assigned
                * for a script unless it is designated to be printed in the footer.
                * See: `wp_register_script` .
                */
                unset($asset->extra['group']);
                $asset->add_data('group', 1);
            }
            return $asset;
        }
        error_log(
            sprintf(
                'Failed to register asset %s with src %s. Verify that the file exists.',
                $handle,
                $src
            )
        );
        return null;
    }


    /**
     * Registers all the WordPress packages scripts that are in the standardized
     * `build/` location.
     *
     * @param WP_Scripts $scripts WP_Scripts instance.
     */
    public function registerScripts(WP_Scripts $scripts): void
    {
        remove_action('wp_default_scripts', [$this, 'registerScripts']);
        $assets_path  = $this->assetsPathBase() . $this->assetsPath();
        $asset_files  = $this->getManifest();
        $entry_points = $this->getEntryPoints();

        foreach ($entry_points as $entry_point) {
            $handle = $this->assetHandle($entry_point);
            // Get the path from root directory as expected by `$this->url`.
            $asset_path = $this->assetsPath() . $asset_files[ $entry_point . Assets::FILE_EXT_JS ];

            if (! empty($asset_files[ $entry_point . Assets::FILE_EXT_PHP ])) {
                $asset_file   = $assets_path . $asset_files[ $entry_point . Assets::FILE_EXT_PHP ];
                $asset        = file_exists($asset_file) ? require($asset_file) : [];
                $dependencies = $asset['dependencies'] ?? [];
                $version      = $asset['version'] ?? null;
                // remove cyclical dependencies, if any
                if ($dependencies && ($key = array_search($handle, $dependencies, true)) !== false) {
                    unset($dependencies[ $key ]);
                }
            }
            $script = $this->addOrUpdateAsset(
                $scripts,
                $handle,
                $asset_path,
                $dependencies ?? [],
                $version ?? $this->version(),
                ['in_footer' => true]
            );

            if ($script instanceof _WP_Dependency) {
                $this->assets['js'][ $handle ] = $script;
            }
        }
    }


    /**
     * Registers all the packages and domain styles that are in the build folder.
     *
     * @param WP_Styles $styles WP_Styles instance.
     */
    public function registerStyles(WP_Styles $styles): void
    {
        remove_action('wp_default_styles', [$this, 'registerStyles']);
        $asset_files  = $this->getManifest();
        $entry_points = $this->getEntryPoints();

        foreach ($entry_points as $entry_point) {
            $handle = $this->assetHandle($entry_point);
            if (! empty($asset_files[ $entry_point . Assets::FILE_EXT_CSS ])) {
                $style = $this->addOrUpdateAsset(
                    $styles,
                    $handle,
                    $this->assetsPath() . $asset_files[ $entry_point . Assets::FILE_EXT_CSS ],
                    [],
                    $this->version(),
                    'all'
                );
                if ($style instanceof _WP_Dependency) {
                    $this->assets['css'][ $handle ] = $style;
                }
            }
        }
    }


    public function registerDependencies()
    {
        global $wp_scripts;
        // Enqueue all the registered scripts and styles.
        foreach ($this->assets['js'] as $script) {
            $this->registerJsDependencies($wp_scripts, $script);
        }
    }


    public function registerJsDependencies(WP_Scripts $wp_scripts, _WP_Dependency $asset)
    {
        foreach ($asset->deps as $handle) {
            $js_asset = $wp_scripts->query($handle);
            if (
                ($asset->handle === Assets::HANDLE_ADMIN || $asset->handle === Assets::HANDLE_PUBLIC)
                && $js_asset === false
            ) {
                $dependency_path = Assets::PATH . "/vendor/$handle.min.js";
                if (is_readable(EVENTS_CALENDAR_PLUS_BASE_PATH . $dependency_path)) {
                    $wp_scripts->add(
                        $handle,
                        EVENTS_CALENDAR_PLUS_BASE_URL . $dependency_path,
                        [],
                        $this->version(),
                        ['in_footer' => true]
                    );
                }
            }
        }
    }
}
