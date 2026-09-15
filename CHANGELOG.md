# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Releases

### 1.0.15


#### Fixed
 - [Fix: register Calendar Plus assets when script registry already exists (#2294)](https://github.com/eventespresso/cafe/pull/2294)







### 1.0.14

#### Added
 - [Load Polyfills If WP Version Is Less Than 6.8 (#2097)](https://github.com/eventespresso/cafe/pull/2097)

#### Fixed
 - [Include Events Whose Start Falls Within The Queried Date Range (#2186)](https://github.com/eventespresso/cafe/pull/2186)
 - [Fix: Calendar Plus API pagination silently drops the last page (#2261)](https://github.com/eventespresso/cafe/pull/2261)
 - [Fixed Disabling Datetimes/Tickets Deletions if They Are Linked Item and Last One (Barista#1488) (#2276)](https://github.com/eventespresso/cafe/pull/2276)
 - [Fixed Calendar+ Events Timezone Conversions. (#2089)](https://github.com/eventespresso/cafe/pull/2089)

#### Changed
 - [SaaS. Add mods for Events Calendar Plus (#2104)](https://github.com/eventespresso/cafe/pull/2104)
 - [Added Debounced Filters Apply while User Type (Barista#1487) (#2275)](https://github.com/eventespresso/cafe/pull/2275)
 - [Calendar Plus Category Text Color (Barista#1503) (#2277)](https://github.com/eventespresso/cafe/pull/2277)
 - [Bump DDEV to v1-24-10 (#1484) + Claude Docs (#1493) + Extract Eslint Rules (#1501) (Barista#1514) (#2283)](https://github.com/eventespresso/cafe/pull/2283)
 - [Fixed Events Timezone to Match Website Timezone in Calendar (Barista#1522) (#2285)](https://github.com/eventespresso/cafe/pull/2285)





### [1.0.13]
 - no changes - version bump due to internal system updates


### [1.0.12]

#### Changed
 - [Removed Eventespresso i18n Dependencies From Calendar+ (Barista#1479) (#2075)](https://github.com/eventespresso/cafe/pull/2075)


### [1.0.11]
#### Added
 - [Add support for Events Calendar Plus Anywhere (#2070)](https://github.com/eventespresso/cafe/pull/2070)

#### Fixed
 - [Fix events not showing on end of month (#2068)](https://github.com/eventespresso/cafe/pull/2068)
 - [Added inputs For editing filter labels applied to Calendar+ filters (Barista#1469) (#2071)](https://github.com/eventespresso/cafe/pull/2071)


### [1.0.10]
#### Fixed
 - [Missing file fix]


### [1.0.9]
#### Fixed
 - [Fixed Z Index Issue Of Popover For Horizontal Filters (Barista#1439) (#1969)](https://github.com/eventespresso/cafe/pull/1969)
 - [New Events Calendar Plus Shortcodes plugin (#1752)](https://github.com/eventespresso/cafe/pull/1752)
 - [Filter Customization and Shortcodes (Barista#1381) (#1981)](https://github.com/eventespresso/cafe/pull/1981)
 - [Remove Duplicate Events (Barista#1451) (#1996)](https://github.com/eventespresso/cafe/pull/1996)


### [1.0.8]
#### Fixed
- [Fixed Incorrect Import of i18n Utility from EE Core (Barista#1438) (#1953)](https://github.com/eventespresso/cafe/pull/1953)


### [1.0.7]
#### Changed
 - [Fixed Empty Placeholder Showing On Detail Modals When No Tags Or Categories Are There (Barista#1431) (#1949)](https://github.com/eventespresso/cafe/pull/1949)
 - [Removed Events Calendar Plus Address 2 Duplications (#1948)](https://github.com/eventespresso/cafe/pull/1948)
 - [Fixed Get Direction From Complete Address Details (Barista#1436) (#1950)](https://github.com/eventespresso/cafe/pull/1950)
 - [Add Setting for Category Text Color (Barista#1432) (#1951)](https://github.com/eventespresso/cafe/pull/1951)
 - [Fix Events Calendar Plus changelog and readme (#1952)](https://github.com/eventespresso/cafe/pull/1952)
 - [Fixed Incorrect Import of i18n Utility from EE Core (Barista#1438) (#1953)](https://github.com/eventespresso/cafe/pull/1953)


### [1.0.6]
#### Fixed
 - [Fix Events CalendarPlus API URL on Multi-Site (#1934)](https://github.com/eventespresso/cafe/pull/1934)
 - [Fix Events CalendarPlus API URL on Multi-Site (Barista#1430) (#1935)](https://github.com/eventespresso/cafe/pull/1935)


### [1.0.5]
#### Fixed
 - [Fix WP readme.txt file]


### [1.0.4]
#### Added
 - [Add Calendar Plus Version String to DOM and API Responses (#1896)](https://github.com/eventespresso/cafe/pull/1896)
 - [Add Calendar Plus Support Admin Page (#1898)](https://github.com/eventespresso/cafe/pull/1898)

#### Fixed
 - [Prevent Migrations From Running on New Calendar Plus Activations (#1905)](https://github.com/eventespresso/cafe/pull/1905)

#### Changed
 - [Fix - All Filters Checkbox Filtering Issue (Barista#1417) (#1890)](https://github.com/eventespresso/cafe/pull/1890)
 - [Refactor Events Calendar Plus Data Loading and Add Data Migration Management (#1881)](https://github.com/eventespresso/cafe/pull/1881)
 - [CalendarPlus Data fetching with api (Barista#1410) (#1902)](https://github.com/eventespresso/cafe/pull/1902)
 - [Fix/Calendar Plus Crash (Barista#1425) (#1903)](https://github.com/eventespresso/cafe/pull/1903)
 - [Add entity caches to ee event adapter (#1868)](https://github.com/eventespresso/cafe/pull/1868)


### [1.0.3]
#### Added
 - [Implemented Settings for Week Start Day Selection (Barista#1401) (#1835)](https://github.com/eventespresso/cafe/pull/1835)
 - [Add Query Params Support (Barista#1402)](https://github.com/eventespresso/barista/pull/1402)

#### Fixed
 - [Add entity caching and only query events from the previous month onwards  within Calendar Plus (#1871)](https://github.com/eventespresso/cafe/pull/1871)
 - [Fix Events Calendar Plus Asset Loading from Barista (#1852)](https://github.com/eventespresso/cafe/pull/1852)

#### Changed
 - [Refactored Filters Logic into Predicates (Barista#1404) (#1839)](https://github.com/eventespresso/cafe/pull/1839)
 - [Fixed Styles to Calendar-Domain Specific (Barista#1405) (#1847)](https://github.com/eventespresso/cafe/pull/1847)
 - [Rename Version Files and Fallback to Main File for Version (#1880)](https://github.com/eventespresso/cafe/pull/1880)
 - [Seperated i18n Utility Logic for Calendar Domains (Barista#1403) (#1884)](https://github.com/eventespresso/cafe/pull/1884)


### [1.0.2]
#### Fixed
 - [Allow eslint rule jsx no new object as prop (Barista#1384) (#1814)](https://github.com/eventespresso/cafe/pull/1814)
 - [Fix/cp/default settings migration (#1816)](https://github.com/eventespresso/cafe/pull/1816)
 - [Events Calendar Plus: Dont load Draft Events (#1817)](https://github.com/eventespresso/cafe/pull/1817)

#### Changed
 - [Fix/line clamp issue on safari (Barista#1395) (#1820)](https://github.com/eventespresso/cafe/pull/1820)
 - [fixed list style of agenda view to none (Barista#1391) (#1821)](https://github.com/eventespresso/cafe/pull/1821)
 - [fixed placeholder issue in edtr and p tag issue (Barista#1387) (#1822)](https://github.com/eventespresso/cafe/pull/1822)
 - [fixed events category color crash (Barista#1398) (#1826)](https://github.com/eventespresso/cafe/pull/1826)
 - [Fix Collapse Filters on Narrow Screens (Barista#1392) (#1828)](https://github.com/eventespresso/cafe/pull/1828)
 - [Added Admin Setting to Show Filters on Top Instead of Sidebar (Barista#1396) (#1829)](https://github.com/eventespresso/cafe/pull/1829)


### [1.0.1]
#### Added
 - [Calendar Plus Event Custom Post Type (#1732)](https://github.com/eventespresso/cafe/pull/1732)
 - [Add Default Dark Mode Colors (Barista#1380) (#1803)](https://github.com/eventespresso/cafe/pull/1803)

#### Fixed
 - [Calendar Plus WordPress Fixes (#1775)](https://github.com/eventespresso/cafe/pull/1775)
 - [More Calendar Plus WordPress Fixes (#1780)](https://github.com/eventespresso/cafe/pull/1780)
 - [Fixed Backspace in Date Input Crashes Calendar (Barista#1367) #1792](https://github.com/eventespresso/cafe/pull/1792)
 - [Fixed eventCategoryColors When Settings Are Not Saved (Barista#1375) (#1793)](https://github.com/eventespresso/cafe/pull/1793)
 - [Fixed Dark Mode Upcoming Calendar Dates Missing (Barista#1376) (#1794)](https://github.com/eventespresso/cafe/pull/1794)
 - [Fix Dark Mode Filters (Barista#1377) (#1795)](https://github.com/eventespresso/cafe/pull/1795)
 - [Fix Styling Issues (Barista#1383) (#1804)](https://github.com/eventespresso/cafe/pull/1804)
 - [Fix/allow eslint rule jsx no new object as prop (Barista#1384) (#1814)](https://github.com/eventespresso/cafe/pull/1814)

#### Changed
 - [Refactor Calendar+ Asset Loading (#1729)](https://github.com/eventespresso/cafe/pull/1729)
 - [Rename Plugin to Events Calendar Plus (#1751)](https://github.com/eventespresso/cafe/pull/1751)
 - [added datetime id to querystring for event espresso events adapter (#1766)](https://github.com/eventespresso/cafe/pull/1766)
 - [Mod/Add or Update readme Files (#1748)](https://github.com/eventespresso/cafe/pull/1748)
