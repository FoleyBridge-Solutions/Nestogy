/**
 * Main
 */

'use strict';

window.isRtl = window.Helpers.isRtl();
window.isDarkStyle = window.Helpers.isDarkStyle();
let menu,
    animate,
    isHorizontalLayout = false;

if (document.getElementById('layout-menu')) {
    isHorizontalLayout = document.getElementById('layout-menu').classList.contains('menu-horizontal');
}

(function () {
    setTimeout(function () {
        window.Helpers.initCustomOptionCheck();
    }, 1000);

    // Initialize menu
    //-----------------

    let layoutMenuEl = document.querySelectorAll('#layout-menu');
    layoutMenuEl.forEach(function (element) {
        menu = new Menu(element, {
            orientation: isHorizontalLayout ? 'horizontal' : 'vertical',
            closeChildren: isHorizontalLayout ? true : false,
            // ? This option only works with Horizontal menu
            showDropdownOnHover: localStorage.getItem('templateCustomizer-' + templateName + '--ShowDropdownOnHover') // If value(showDropdownOnHover) is set in local storage
                ? localStorage.getItem('templateCustomizer-' + templateName + '--ShowDropdownOnHover') === 'true' // Use the local storage value
                : window.templateCustomizer !== undefined // If value is set in config.js
                    ? window.templateCustomizer.settings.defaultShowDropdownOnHover // Use the config.js value
                    : true // Use this if you are not using the config.js and want to set value directly from here
        });
        // Change parameter to true if you want scroll animation
        window.Helpers.scrollToActive((animate = false));
        window.Helpers.mainMenu = menu;
    });

    // Initialize menu togglers and bind click on each
    let menuToggler = document.querySelectorAll('.layout-menu-toggle');
    menuToggler.forEach(item => {
        item.addEventListener('click', event => {
            event.preventDefault();
            window.Helpers.toggleCollapsed();
            // Enable menu state with local storage support if enableMenuLocalStorage = true from config.js
            if (config.enableMenuLocalStorage && !window.Helpers.isSmallScreen()) {
                try {
                    localStorage.setItem(
                        'templateCustomizer-' + templateName + '--LayoutCollapsed',
                        String(window.Helpers.isCollapsed())
                    );
                    // Update customizer checkbox state on click of menu toggler
                    let layoutCollapsedCustomizerOptions = document.querySelector('.template-customizer-layouts-options');
                    if (layoutCollapsedCustomizerOptions) {
                        let layoutCollapsedVal = window.Helpers.isCollapsed() ? 'collapsed' : 'expanded';
                        layoutCollapsedCustomizerOptions.querySelector(`input[value="${layoutCollapsedVal}"]`).click();
                    }
                } catch (e) { }
            }
        });
    });

    // Display menu toggle (layout-menu-toggle) on hover with delay
    let delay = function (elem, callback) {
        let timeout = null;
        elem.onmouseenter = function () {
            // Set timeout to be a timer which will invoke callback after 300ms (not for small screen)
            if (!Helpers.isSmallScreen()) {
                timeout = setTimeout(callback, 300);
            } else {
                timeout = setTimeout(callback, 0);
            }
        };

        elem.onmouseleave = function () {
            // Clear any timers set to timeout
            document.querySelector('.layout-menu-toggle').classList.remove('d-block');
            clearTimeout(timeout);
        };
    };
    if (document.getElementById('layout-menu')) {
        delay(document.getElementById('layout-menu'), function () {
            // not for small screen
            if (!Helpers.isSmallScreen()) {
                document.querySelector('.layout-menu-toggle').classList.add('d-block');
            }
        });
    }

    // Menu swipe gesture

    // Detect swipe gesture on the target element and call swipe In
    window.Helpers.swipeIn('.drag-target', function (e) {
        window.Helpers.setCollapsed(false);
    });

    // Detect swipe gesture on the target element and call swipe Out
    window.Helpers.swipeOut('#layout-menu', function (e) {
        if (window.Helpers.isSmallScreen()) window.Helpers.setCollapsed(true);
    });

    // Display in main menu when menu scrolls
    let menuInnerContainer = document.getElementsByClassName('menu-inner'),
        menuInnerShadow = document.getElementsByClassName('menu-inner-shadow')[0];
    if (menuInnerContainer.length > 0 && menuInnerShadow) {
        menuInnerContainer[0].addEventListener('ps-scroll-y', function () {
            if (this.querySelector('.ps__thumb-y').offsetTop) {
                menuInnerShadow.style.display = 'block';
            } else {
                menuInnerShadow.style.display = 'none';
            }
        });
    }

    // Update light/dark image based on current style
    function switchImage(style) {
        if (style === 'system') {
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                style = 'dark';
            } else {
                style = 'light';
            }
        }
        const switchImagesList = [].slice.call(document.querySelectorAll('[data-app-' + style + '-img]'));
        switchImagesList.map(function (imageEl) {
            const setImage = imageEl.getAttribute('data-app-' + style + '-img');
            imageEl.src = assetsPath + 'img/' + setImage; // Using window.assetsPath to get the exact relative path
        });
    }

    //Style Switcher (Light/Dark/System Mode)
    let styleSwitcher = document.querySelector('.dropdown-style-switcher');

    // Get style from local storage or use 'system' as default
    let storedStyle =
        localStorage.getItem('templateCustomizer-' + templateName + '--Style') || //if no template style then use Customizer style
        (window.templateCustomizer?.settings?.defaultStyle ?? 'light'); //!if there is no Customizer then use default style as light

    // Set style on click of style switcher item if template customizer is enabled
    if (window.templateCustomizer && styleSwitcher) {
        let styleSwitcherItems = [].slice.call(styleSwitcher.children[1].querySelectorAll('.dropdown-item'));
        styleSwitcherItems.forEach(function (item) {
            item.addEventListener('click', function () {
                let currentStyle = this.getAttribute('data-theme');
                if (currentStyle === 'light') {
                    window.templateCustomizer.setStyle('light');
                } else if (currentStyle === 'dark') {
                    window.templateCustomizer.setStyle('dark');
                } else {
                    window.templateCustomizer.setStyle('system');
                }
            });
        });

        // Update style switcher icon based on the stored style

        const styleSwitcherIcon = styleSwitcher.querySelector('i');

        if (storedStyle === 'light') {
            styleSwitcherIcon.classList.add('bx-sun');
            new bootstrap.Tooltip(styleSwitcherIcon, {
                title: 'Light Mode',
                fallbackPlacements: ['bottom']
            });
        } else if (storedStyle === 'dark') {
            styleSwitcherIcon.classList.add('bx-moon');
            new bootstrap.Tooltip(styleSwitcherIcon, {
                title: 'Dark Mode',
                fallbackPlacements: ['bottom']
            });
        } else {
            styleSwitcherIcon.classList.add('bx-desktop');
            new bootstrap.Tooltip(styleSwitcherIcon, {
                title: 'System Mode',
                fallbackPlacements: ['bottom']
            });
        }
    }

    // Run switchImage function based on the stored style
    switchImage(storedStyle);

    // Internationalization (Language Dropdown)
    // ---------------------------------------

    if (typeof i18next !== 'undefined' && typeof i18NextHttpBackend !== 'undefined') {
        i18next
            .use(i18NextHttpBackend)
            .init({
                lng: window.templateCustomizer ? window.templateCustomizer.settings.lang : 'en',
                debug: false,
                fallbackLng: 'en',
                backend: {
                    loadPath: assetsPath + 'json/locales/{{lng}}.json'
                },
                returnObjects: true
            })
            .then(function (t) {
                localize();
            });
    }

    let languageDropdown = document.getElementsByClassName('dropdown-language');

    if (languageDropdown.length) {
        let dropdownItems = languageDropdown[0].querySelectorAll('.dropdown-item');

        for (let i = 0; i < dropdownItems.length; i++) {
            dropdownItems[i].addEventListener('click', function () {
                let currentLanguage = this.getAttribute('data-language');
                let textDirection = this.getAttribute('data-text-direction');

                for (let sibling of this.parentNode.children) {
                    var siblingEle = sibling.parentElement.parentNode.firstChild;

                    // Loop through each sibling and push to the array
                    while (siblingEle) {
                        if (siblingEle.nodeType === 1 && siblingEle !== siblingEle.parentElement) {
                            siblingEle.querySelector('.dropdown-item').classList.remove('active');
                        }
                        siblingEle = siblingEle.nextSibling;
                    }
                }
                this.classList.add('active');

                i18next.changeLanguage(currentLanguage, (err, t) => {
                    window.templateCustomizer ? window.templateCustomizer.setLang(currentLanguage) : '';
                    directionChange(textDirection);
                    if (err) return console.log('something went wrong loading', err);
                    localize();
                });
            });
        }
        function directionChange(textDirection) {
            if (textDirection === 'rtl') {
                if (localStorage.getItem('templateCustomizer-' + templateName + '--Rtl') !== 'true')
                    window.templateCustomizer ? window.templateCustomizer.setRtl(true) : '';
            } else {
                if (localStorage.getItem('templateCustomizer-' + templateName + '--Rtl') === 'true')
                    window.templateCustomizer ? window.templateCustomizer.setRtl(false) : '';
            }
        }
    }

    function localize() {
        let i18nList = document.querySelectorAll('[data-i18n]');
        // Set the current language in dd
        let currentLanguageEle = document.querySelector('.dropdown-item[data-language="' + i18next.language + '"]');

        if (currentLanguageEle) {
            currentLanguageEle.click();
        }

        i18nList.forEach(function (item) {
            item.innerHTML = i18next.t(item.dataset.i18n);
        });
    }

    // Notification
    // ------------
    const notificationMarkAsReadAll = document.querySelector('.dropdown-notifications-all');
    const notificationMarkAsReadList = document.querySelectorAll('.dropdown-notifications-read');

    // Notification: Mark as all as read
    if (notificationMarkAsReadAll) {
        notificationMarkAsReadAll.addEventListener('click', event => {
            notificationMarkAsReadList.forEach(item => {
                item.closest('.dropdown-notifications-item').classList.add('marked-as-read');
            });
        });
    }
    // Notification: Mark as read/unread onclick of dot
    if (notificationMarkAsReadList) {
        notificationMarkAsReadList.forEach(item => {
            item.addEventListener('click', event => {
                item.closest('.dropdown-notifications-item').classList.toggle('marked-as-read');
            });
        });
    }

    // Notification: Mark as read/unread onclick of dot
    const notificationArchiveMessageList = document.querySelectorAll('.dropdown-notifications-archive');
    notificationArchiveMessageList.forEach(item => {
        item.addEventListener('click', event => {
            item.closest('.dropdown-notifications-item').remove();
        });
    });

    // Init helpers & misc
    // --------------------

    // Init BS Tooltip
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Accordion active class
    const accordionActiveFunction = function (e) {
        if (e.type == 'show.bs.collapse' || e.type == 'show.bs.collapse') {
            e.target.closest('.accordion-item').classList.add('active');
        } else {
            e.target.closest('.accordion-item').classList.remove('active');
        }
    };

    const accordionTriggerList = [].slice.call(document.querySelectorAll('.accordion'));
    const accordionList = accordionTriggerList.map(function (accordionTriggerEl) {
        accordionTriggerEl.addEventListener('show.bs.collapse', accordionActiveFunction);
        accordionTriggerEl.addEventListener('hide.bs.collapse', accordionActiveFunction);
    });

    // If layout is RTL add .dropdown-menu-end class to .dropdown-menu
    // if (isRtl) {
    //   Helpers._addClass('dropdown-menu-end', document.querySelectorAll('#layout-navbar .dropdown-menu'));
    // }

    // Auto update layout based on screen size
    window.Helpers.setAutoUpdate(true);

    // Toggle Password Visibility
    window.Helpers.initPasswordToggle();

    // Speech To Text
    window.Helpers.initSpeechToText();

    // Init PerfectScrollbar in Navbar Dropdown (i.e notification)
    window.Helpers.initNavbarDropdownScrollbar();

    let horizontalMenuTemplate = document.querySelector("[data-template^='horizontal-menu']");
    if (horizontalMenuTemplate) {
        // if screen size is small then set navbar fixed
        if (window.innerWidth < window.Helpers.LAYOUT_BREAKPOINT) {
            window.Helpers.setNavbarFixed('fixed');
        } else {
            window.Helpers.setNavbarFixed('');
        }
    }

    // On window resize listener
    // -------------------------
    window.addEventListener(
        'resize',
        function (event) {
            // Hide open search input and set value blank
            if (window.innerWidth >= window.Helpers.LAYOUT_BREAKPOINT) {
                if (document.querySelector('.search-input-wrapper')) {
                    document.querySelector('.search-input-wrapper').classList.add('d-none');
                    document.querySelector('.search-input').value = '';
                }
            }
            // Horizontal Layout : Update menu based on window size
            if (horizontalMenuTemplate) {
                // if screen size is small then set navbar fixed
                if (window.innerWidth < window.Helpers.LAYOUT_BREAKPOINT) {
                    window.Helpers.setNavbarFixed('fixed');
                } else {
                    window.Helpers.setNavbarFixed('');
                }
                setTimeout(function () {
                    if (window.innerWidth < window.Helpers.LAYOUT_BREAKPOINT) {
                        if (document.getElementById('layout-menu')) {
                            if (document.getElementById('layout-menu').classList.contains('menu-horizontal')) {
                                menu.switchMenu('vertical');
                            }
                        }
                    } else {
                        if (document.getElementById('layout-menu')) {
                            if (document.getElementById('layout-menu').classList.contains('menu-vertical')) {
                                menu.switchMenu('horizontal');
                            }
                        }
                    }
                }, 100);
            }
        },
        true
    );

    // Manage menu expanded/collapsed with templateCustomizer & local storage
    //------------------------------------------------------------------

    // If current layout is horizontal OR current window screen is small (overlay menu) than return from here
    if (isHorizontalLayout || window.Helpers.isSmallScreen()) {
        return;
    }

    // If current layout is vertical and current window screen is > small

    // Auto update menu collapsed/expanded based on the themeConfig
    if (typeof TemplateCustomizer !== 'undefined') {
        if (window.templateCustomizer.settings.defaultMenuCollapsed) {
            window.Helpers.setCollapsed(true, false);
        } else {
            window.Helpers.setCollapsed(false, false);
        }
    }

    // Manage menu expanded/collapsed state with local storage support If enableMenuLocalStorage = true in config.js
    if (typeof config !== 'undefined') {
        if (config.enableMenuLocalStorage) {
            try {
                if (localStorage.getItem('templateCustomizer-' + templateName + '--LayoutCollapsed') !== null)
                    window.Helpers.setCollapsed(
                        localStorage.getItem('templateCustomizer-' + templateName + '--LayoutCollapsed') === 'true',
                        false
                    );
            } catch (e) { }
        }
    }
})();


if (typeof $ !== 'undefined') {
    $(function () {
        initializeUI();
        setupSearch();
    });

    

    function initializeUI() {
        window.Helpers.initSidebarToggle();
        initializeSearchComponents();
    }

    function initializeSearchComponents() {
        const searchToggler = $('.search-toggler');
        const searchInputWrapper = $('.search-input-wrapper');
        const searchInput = $('.search-input');

        bindSearchToggler(searchToggler, searchInputWrapper, searchInput);
        bindSearchShortcut(searchInputWrapper, searchInput);
    }

    function bindSearchToggler(toggler, wrapper, input) {
        toggler.on('click', function () {
            wrapper.toggleClass('d-none');
            input.focus();
            $('.content-backdrop').addClass('show').removeClass('fade');
        });
    }
    
    $('.search-input').bind('typeahead:render', function() {
        $('.content-backdrop').addClass('show').removeClass('fade');
    }).bind('typeahead:close', function() {
        $('.search-input').val('');
        $('.search-input-wrapper').addClass('d-none');
        $('.content-backdrop').addClass('fade').removeClass('show');
    });
    

    function bindSearchShortcut(wrapper, input) {
        $(document).on('keydown', function (event) {
            if (event.ctrlKey && event.which === 191) {
                wrapper.toggleClass('d-none');
                input.focus();
                $('.content-backdrop').addClass('show').removeClass('fade');
            }
        });
    }

function setupSearch() {
    var searchInput = $('.search-input');
    if (searchInput.length) {

        function initializeBloodhound(category) {
            return new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    url: '/ajax/ajax.php?search=%QUERY%&category=' + category,
                    wildcard: '%QUERY',
                    transform: function(response) {
                        return $.map(response, function(item) {
                            return {
                                name: item.name,
                                id: item.id,
                                url: item.url
                            };
                        });
                    }
                }
            });
        }

        // Initialize Bloodhound engines for each category
        var clientsEngine = initializeBloodhound('clients');
        var contactsEngine = initializeBloodhound('contacts');
        var ticketsEngine = initializeBloodhound('tickets');
        var documentsEngine = initializeBloodhound('documents');
        var loginsEngine = initializeBloodhound('logins');
        var ticketRepliesEngine = initializeBloodhound('ticketReplies');
        var assetsEngine = initializeBloodhound('assets');
        var invoicesEngine = initializeBloodhound('invoices');

        // Create a single card container outside the typeahead logic with added styling
        const resultsCard = $('<div class="card mt-3 search-results-card"><div class="card-body"><div class="search-results"></div></div></div>');
        $('.search-input-wrapper').append(resultsCard);
        resultsCard.css({
            'background-color': '#fff !important',    // White background
            'box-shadow': '0 4px 8px rgba(0, 0, 0, 0.1) !important',  // Soft shadow for elevation
            'padding': '10px !important',              // Padding inside the card
            'position': 'absolute !important',         // Position it above other content
            'z-index': '1000 !important',              // Ensure it's on top
            'width': '100% !important'                 // Full width of the input wrapper
        });
        resultsCard.hide();  // Hide initially

        // Function to prepare templates for each category
        function categoryTemplate(categoryName, engine) {
            // Define icons for each category
            const icons = {
                clients: 'bx bx-user',
                contacts: 'bx bx-phone',
                tickets: 'bx bx-ticket',
                documents: 'bx bx-file',
                logins: 'bx bx-lock',
                ticketReplies: 'bx bx-chat',
                assets: 'bx bx-box',
                invoices: 'bx bx-receipt'
            };
        
            // Get the appropriate icon for the current category
            const iconClass = icons[categoryName] || 'bx bx-folder'; // Default icon if category is not found
        
            return {
                name: categoryName,
                display: 'name',
                source: engine,
                templates: {
                    header: `<h6 class="suggestions-header text-primary mb-0 mx-3 mt-3 pb-2">${categoryName.charAt(0).toUpperCase() + categoryName.slice(1)}</h6>`,
                    suggestion: function(data) {
                        return `
                            <div class="search-result-item">
                                <i class="${iconClass} me-2"></i>
                                <a href="${data.url}" class="align-middle">${data.name}</a>
                            </div>`;
                    }
                }
            };
        }
        
        // Initialize Typeahead with Templates
        $('.search-input').typeahead({
            minLength: 2,
            highlight: true
        },
        categoryTemplate('clients', clientsEngine),
        categoryTemplate('contacts', contactsEngine),
        categoryTemplate('tickets', ticketsEngine),
        categoryTemplate('documents', documentsEngine),
        categoryTemplate('logins', loginsEngine),
        categoryTemplate('ticketReplies', ticketRepliesEngine),
        categoryTemplate('assets', assetsEngine),
        categoryTemplate('invoices', invoicesEngine)
        ).on('typeahead:select', function(e, suggestion) {
            // Redirect to the selected item's URL
            window.location.href = suggestion.url;
        });
        
        

        // Bindings for typeahead behavior
        $('.search-input').bind('typeahead:render', function(ev, suggestions, async, dataset) {
            if (!dataset || !suggestions.length) return;

            const categoryHeader = `<h6 class="suggestions-header text-primary mb-0 mx-3 mt-3 pb-2">${dataset.name.charAt(0).toUpperCase() + dataset.name.slice(1)}</h6>`;
            const resultsContainer = resultsCard.find('.search-results');
            
            resultsContainer.append(categoryHeader);
            suggestions.forEach(function(suggestion) {
                const suggestionHTML = `
                    <div class="search-result-item">
                        <i class="bx bx-user me-2"></i>
                        <a href="${suggestion.url}" class="align-middle">${suggestion.name}</a>
                    </div>`;
                resultsContainer.append(suggestionHTML);
            });
            resultsCard.show();  // Show the card when there are results
        }).bind('typeahead:close', function() {
            $('.search-input').val('');
            $('.search-input-wrapper').addClass('d-none');
            $('.content-backdrop').addClass('fade').removeClass('show');
            resultsCard.hide();  // Hide the card when search is closed
            resultsCard.find('.search-results').empty();  // Clear the results
        });

        setupPerfectScrollbar();
    }
}

    
    
    function setupPerfectScrollbar() {
        $('.navbar-search-suggestion').each(function () {
            new PerfectScrollbar($(this)[0], {
                wheelPropagation: false,
                suppressScrollX: true
            });
        });
    }
}

