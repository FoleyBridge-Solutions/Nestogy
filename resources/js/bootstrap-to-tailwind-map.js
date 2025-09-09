/**
 * Bootstrap to Tailwind CSS Class Mapping
 * This mapping helps with the migration from Bootstrap to Tailwind
 */

export const classMap = {
    // Layout & Container
    'container': 'container mx-auto px-4',
    'container-fluid': 'w-full px-4',
    'container-sm': 'container mx-auto px-4 max-w-3xl',
    'container-md': 'container mx-auto px-4 max-w-5xl',
    'container-lg': 'container mx-auto px-4 max-w-6xl',
    'container-xl': 'container mx-auto px-4 max-w-7xl',
    
    // Grid System
    'row': 'flex flex-wrap -mx-4',
    'col': 'flex-1 px-4',
    'col-1': 'w-1/12 px-4',
    'col-2': 'w-1/6 px-4',
    'col-3': 'w-1/4 px-4',
    'col-4': 'w-1/3 px-4',
    'col-5': 'w-5/12 px-4',
    'col-6': 'w-1/2 px-4',
    'col-7': 'w-7/12 px-4',
    'col-8': 'w-2/3 px-4',
    'col-9': 'w-3/4 px-4',
    'col-10': 'w-5/6 px-4',
    'col-11': 'w-11/12 px-4',
    'col-12': 'w-full px-4',
    
    // Responsive columns
    'col-sm-6': 'sm:w-1/2 px-4',
    'col-md-3': 'md:w-1/4 px-4',
    'col-md-4': 'md:w-1/3 px-4',
    'col-md-6': 'md:w-1/2 px-4',
    'col-md-8': 'md:w-2/3 px-4',
    'col-lg-3': 'lg:w-1/4 px-4',
    'col-lg-4': 'lg:w-1/3 px-4',
    'col-lg-6': 'lg:w-1/2 px-4',
    'col-lg-8': 'lg:w-2/3 px-4',
    
    // Display utilities
    'd-none': 'hidden',
    'd-inline': 'inline',
    'd-inline-block': 'inline-block',
    'd-block': 'block',
    'd-flex': 'flex',
    'd-inline-flex': 'inline-flex',
    'd-grid': 'grid',
    
    // Flexbox utilities
    'justify-content-start': 'justify-start',
    'justify-content-end': 'justify-end',
    'justify-content-center': 'justify-center',
    'justify-content-between': 'justify-between',
    'justify-content-around': 'justify-around',
    'justify-content-evenly': 'justify-evenly',
    'align-items-start': 'items-start',
    'align-items-end': 'items-end',
    'align-items-center': 'items-center',
    'align-items-baseline': 'items-baseline',
    'align-items-stretch': 'items-stretch',
    'flex-row': 'flex-row',
    'flex-column': 'flex-col',
    'flex-wrap': 'flex-wrap',
    'flex-nowrap': 'flex-nowrap',
    'flex-shrink-0': 'flex-shrink-0',
    'flex-grow-1': 'flex-grow',
    
    // Spacing - Margin
    'm-0': 'm-0',
    'm-1': 'm-1',
    'm-2': 'm-2',
    'm-3': 'm-3',
    'm-4': 'm-4',
    'm-5': 'm-6',
    'mt-0': 'mt-0',
    'mt-1': 'mt-1',
    'mt-2': 'mt-2',
    'mt-3': 'mt-3',
    'mt-4': 'mt-4',
    'mt-5': 'mt-6',
    'mb-0': 'mb-0',
    'mb-1': 'mb-1',
    'mb-2': 'mb-2',
    'mb-3': 'mb-3',
    'mb-4': 'mb-4',
    'mb-5': 'mb-6',
    'ms-0': 'ml-0',
    'ms-1': 'ml-1',
    'ms-2': 'ml-2',
    'ms-3': 'ml-3',
    'ms-4': 'ml-4',
    'ms-5': 'ml-6',
    'me-0': 'mr-0',
    'me-1': 'mr-1',
    'me-2': 'mr-2',
    'me-3': 'mr-3',
    'me-4': 'mr-4',
    'me-5': 'mr-6',
    'mx-auto': 'mx-auto',
    'my-auto': 'my-auto',
    
    // Spacing - Padding
    'p-0': 'p-0',
    'p-1': 'p-1',
    'p-2': 'p-2',
    'p-3': 'p-3',
    'p-4': 'p-4',
    'p-5': 'p-6',
    'pt-0': 'pt-0',
    'pt-1': 'pt-1',
    'pt-2': 'pt-2',
    'pt-3': 'pt-3',
    'pt-4': 'pt-4',
    'pt-5': 'pt-6',
    'pb-0': 'pb-0',
    'pb-1': 'pb-1',
    'pb-2': 'pb-2',
    'pb-3': 'pb-3',
    'pb-4': 'pb-4',
    'pb-5': 'pb-6',
    'ps-0': 'pl-0',
    'ps-1': 'pl-1',
    'ps-2': 'pl-2',
    'ps-3': 'pl-3',
    'ps-4': 'pl-4',
    'ps-5': 'pl-6',
    'pe-0': 'pr-0',
    'pe-1': 'pr-1',
    'pe-2': 'pr-2',
    'pe-3': 'pr-3',
    'pe-4': 'pr-4',
    'pe-5': 'pr-6',
    'px-0': 'px-0',
    'px-1': 'px-1',
    'px-2': 'px-2',
    'px-3': 'px-3',
    'px-4': 'px-4',
    'px-5': 'px-6',
    'py-0': 'py-0',
    'py-1': 'py-1',
    'py-2': 'py-2',
    'py-3': 'py-3',
    'py-4': 'py-4',
    'py-5': 'py-6',
    
    // Gap utilities
    'g-0': 'gap-0',
    'g-1': 'gap-1',
    'g-2': 'gap-2',
    'g-3': 'gap-3',
    'g-4': 'gap-4',
    'g-5': 'gap-6',
    
    // Text utilities
    'text-start': 'text-left',
    'text-end': 'text-right',
    'text-center': 'text-center',
    'text-muted': 'text-gray-600',
    'text-primary': 'text-blue-600',
    'text-secondary': 'text-gray-600',
    'text-success': 'text-green-600',
    'text-danger': 'text-red-600',
    'text-warning': 'text-yellow-600',
    'text-info': 'text-cyan-600',
    'text-light': 'text-gray-100',
    'text-dark': 'text-gray-900',
    'text-white': 'text-white',
    'text-black': 'text-black',
    'text-uppercase': 'uppercase',
    'text-lowercase': 'lowercase',
    'text-capitalize': 'capitalize',
    'text-decoration-none': 'no-underline',
    'fw-bold': 'font-bold',
    'fw-semibold': 'font-semibold',
    'fw-normal': 'font-normal',
    'fw-light': 'font-light',
    'fs-1': 'text-5xl',
    'fs-2': 'text-4xl',
    'fs-3': 'text-3xl',
    'fs-4': 'text-2xl',
    'fs-5': 'text-xl',
    'fs-6': 'text-lg',
    'small': 'text-sm',
    'h1': 'text-5xl font-bold',
    'h2': 'text-4xl font-bold',
    'h3': 'text-3xl font-bold',
    'h4': 'text-2xl font-bold',
    'h5': 'text-xl font-bold',
    'h6': 'text-lg font-bold',
    
    // Background utilities
    'bg-primary': 'bg-blue-600',
    'bg-secondary': 'bg-gray-600',
    'bg-success': 'bg-green-600',
    'bg-danger': 'bg-red-600',
    'bg-warning': 'bg-yellow-500',
    'bg-info': 'bg-cyan-600',
    'bg-light': 'bg-gray-100',
    'bg-dark': 'bg-gray-900',
    'bg-white': 'bg-white',
    'bg-transparent': 'bg-transparent',
    'bg-opacity-10': 'bg-opacity-10',
    'bg-opacity-25': 'bg-opacity-25',
    'bg-opacity-50': 'bg-opacity-50',
    'bg-opacity-75': 'bg-opacity-75',
    
    // Border utilities
    'border': 'border',
    'border-0': 'border-0',
    'border-top': 'border-t',
    'border-end': 'border-r',
    'border-bottom': 'border-b',
    'border-start': 'border-l',
    'border-primary': 'border-blue-600',
    'border-secondary': 'border-gray-600',
    'border-success': 'border-green-600',
    'border-danger': 'border-red-600',
    'border-warning': 'border-yellow-500',
    'border-info': 'border-cyan-600',
    'border-light': 'border-gray-300',
    'border-dark': 'border-gray-900',
    'border-white': 'border-white',
    'rounded': 'rounded',
    'rounded-0': 'rounded-none',
    'rounded-1': 'rounded-sm',
    'rounded-2': 'rounded',
    'rounded-3': 'rounded-lg',
    'rounded-circle': 'rounded-full',
    'rounded-pill': 'rounded-full',
    
    // Shadow utilities
    'shadow': 'shadow',
    'shadow-sm': 'shadow-sm',
    'shadow-lg': 'shadow-lg',
    'shadow-none': 'shadow-none',
    
    // Position utilities
    'position-relative': 'relative',
    'position-absolute': 'absolute',
    'position-fixed': 'fixed',
    'position-sticky': 'sticky',
    'position-static': 'static',
    'top-0': 'top-0',
    'top-50': 'top-1/2',
    'top-100': 'top-full',
    'bottom-0': 'bottom-0',
    'bottom-50': 'bottom-1/2',
    'bottom-100': 'bottom-full',
    'start-0': 'left-0',
    'start-50': 'left-1/2',
    'start-100': 'left-full',
    'end-0': 'right-0',
    'end-50': 'right-1/2',
    'end-100': 'right-full',
    
    // Width & Height utilities
    'w-25': 'w-1/4',
    'w-50': 'w-1/2',
    'w-75': 'w-3/4',
    'w-100': 'w-full',
    'w-auto': 'w-auto',
    'h-25': 'h-1/4',
    'h-50': 'h-1/2',
    'h-75': 'h-3/4',
    'h-100': 'h-full',
    'h-auto': 'h-auto',
    'mw-100': 'max-w-full',
    'mh-100': 'max-h-full',
    'min-vw-100': 'min-w-screen',
    'min-vh-100': 'min-h-screen',
    'vw-100': 'w-screen',
    'vh-100': 'h-screen',
    
    // Button classes
    'btn': 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200',
    'btn-primary': 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'btn-secondary': 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    'btn-success': 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'btn-danger': 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'btn-warning': 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500',
    'btn-info': 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500',
    'btn-light': 'bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-gray-500',
    'btn-dark': 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-500',
    'btn-outline-primary': 'border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white focus:ring-blue-500',
    'btn-outline-secondary': 'border-gray-600 text-gray-600 hover:bg-gray-600 hover:text-white focus:ring-gray-500',
    'btn-outline-success': 'border-green-600 text-green-600 hover:bg-green-600 hover:text-white focus:ring-green-500',
    'btn-outline-danger': 'border-red-600 text-red-600 hover:bg-red-600 hover:text-white focus:ring-red-500',
    'btn-lg': 'px-6 py-3 text-base',
    'btn-sm': 'px-3 py-1.5 text-xs',
    'btn-block': 'w-full justify-center',
    'btn-group': 'inline-flex rounded-md shadow-sm',
    
    // Card components
    'card': 'bg-white rounded-lg shadow-md overflow-hidden',
    'card-body': 'p-6',
    'card-header': 'px-6 py-4 border-b border-gray-200 bg-gray-50',
    'card-footer': 'px-6 py-4 border-t border-gray-200 bg-gray-50',
    'card-title': 'text-lg font-semibold',
    'card-subtitle': 'text-sm text-gray-600',
    'card-text': 'text-gray-700',
    'card-img-top': 'w-full h-48 object-cover',
    
    // Alert components
    'alert': 'px-4 py-3 rounded relative',
    'alert-primary': 'bg-blue-100 border border-blue-400 text-blue-700',
    'alert-secondary': 'bg-gray-100 border border-gray-400 text-gray-700',
    'alert-success': 'bg-green-100 border border-green-400 text-green-700',
    'alert-danger': 'bg-red-100 border border-red-400 text-red-700',
    'alert-warning': 'bg-yellow-100 border border-yellow-400 text-yellow-700',
    'alert-info': 'bg-cyan-100 border border-cyan-400 text-cyan-700',
    'alert-dismissible': 'pr-10',
    
    // Badge components
    'badge': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
    'badge-primary': 'bg-blue-100 text-blue-800',
    'badge-secondary': 'bg-gray-100 text-gray-800',
    'badge-success': 'bg-green-100 text-green-800',
    'badge-danger': 'bg-red-100 text-red-800',
    'badge-warning': 'bg-yellow-100 text-yellow-800',
    'badge-info': 'bg-cyan-100 text-cyan-800',
    
    // Form controls
    'form-control': 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
    'form-control-sm': 'px-2 py-1 text-xs',
    'form-control-lg': 'px-4 py-3 text-base',
    'form-select': 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
    'form-check': 'flex items-center',
    'form-check-input': 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded',
    'form-check-label': 'ml-2 block text-sm text-gray-900',
    'form-label': 'block text-sm font-medium text-gray-700 mb-1',
    'form-text': 'mt-1 text-sm text-gray-600',
    'invalid-feedback': 'mt-1 text-sm text-red-600',
    'valid-feedback': 'mt-1 text-sm text-green-600',
    'is-invalid': 'border-red-300 focus:border-red-500 focus:ring-red-500',
    'is-valid': 'border-green-300 focus:border-green-500 focus:ring-green-500',
    
    // Table classes
    'table': 'min-w-full divide-y divide-gray-200',
    'table-responsive': 'overflow-x-auto',
    'table-striped': '[&>tbody>tr:nth-child(odd)]:bg-gray-50',
    'table-hover': '[&>tbody>tr:hover]:bg-gray-100',
    'table-bordered': 'border border-gray-200',
    'thead-dark': 'bg-gray-800 text-white',
    'thead-light': 'bg-gray-100',
    
    // List group
    'list-group': 'bg-white rounded-lg divide-y divide-gray-200 shadow',
    'list-group-item': 'px-4 py-3 hover:bg-gray-50',
    'list-group-item-action': 'cursor-pointer hover:bg-gray-100',
    
    // Modal components (will need JavaScript replacement)
    'modal': 'fixed inset-0 z-50 overflow-y-auto',
    'modal-dialog': 'relative w-auto mx-auto my-8 max-w-lg',
    'modal-content': 'relative bg-white rounded-lg shadow-xl',
    'modal-header': 'px-6 py-4 border-b border-gray-200',
    'modal-body': 'px-6 py-4',
    'modal-footer': 'px-6 py-4 border-t border-gray-200 flex justify-end space-x-2',
    'modal-title': 'text-lg font-semibold',
    
    // Navigation
    'nav': 'flex space-x-4',
    'nav-item': 'flex',
    'nav-link': 'px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100',
    'nav-pills': 'space-x-1',
    'nav-tabs': 'border-b border-gray-200',
    'navbar': 'bg-white shadow',
    'navbar-brand': 'text-xl font-bold',
    'navbar-nav': 'flex space-x-4',
    'navbar-toggler': 'p-2 rounded-md hover:bg-gray-100',
    
    // Breadcrumb
    'breadcrumb': 'flex space-x-2',
    'breadcrumb-item': 'flex items-center',
    
    // Pagination
    'pagination': 'flex items-center space-x-1',
    'page-item': 'flex',
    'page-link': 'px-3 py-2 border border-gray-300 hover:bg-gray-100',
    
    // Utilities
    'visually-hidden': 'sr-only',
    'invisible': 'invisible',
    'visible': 'visible',
    'overflow-auto': 'overflow-auto',
    'overflow-hidden': 'overflow-hidden',
    'overflow-visible': 'overflow-visible',
    'overflow-scroll': 'overflow-scroll',
    'user-select-all': 'select-all',
    'user-select-auto': 'select-auto',
    'user-select-none': 'select-none',
    'pe-none': 'pointer-events-none',
    'pe-auto': 'pointer-events-auto',
    'opacity-0': 'opacity-0',
    'opacity-25': 'opacity-25',
    'opacity-50': 'opacity-50',
    'opacity-75': 'opacity-75',
    'opacity-100': 'opacity-100',
    
    // Close button
    'btn-close': 'text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500',
    
    // Dropdown (will need JavaScript replacement)
    'dropdown': 'relative inline-block text-left',
    'dropdown-menu': 'absolute z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5',
    'dropdown-item': 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100',
    'dropdown-divider': 'border-t border-gray-200',
    
    // Responsive visibility
    'd-sm-none': 'sm:hidden',
    'd-md-none': 'md:hidden',
    'd-lg-none': 'lg:hidden',
    'd-sm-block': 'sm:block',
    'd-md-block': 'md:block',
    'd-lg-block': 'lg:block',
    'd-sm-flex': 'sm:flex',
    'd-md-flex': 'md:flex',
    'd-lg-flex': 'lg:flex',
};

/**
 * Convert Bootstrap classes to Tailwind classes
 * @param {string} bootstrapClasses - Space-separated Bootstrap classes
 * @returns {string} - Space-separated Tailwind classes
 */
export function convertToTailwind(bootstrapClasses) {
    const classes = bootstrapClasses.split(' ');
    const tailwindClasses = [];
    
    for (const cls of classes) {
        const trimmedClass = cls.trim();
        if (trimmedClass && classMap[trimmedClass]) {
            tailwindClasses.push(classMap[trimmedClass]);
        } else if (trimmedClass) {
            // Keep unknown classes as-is (might be custom classes)
            tailwindClasses.push(trimmedClass);
        }
    }
    
    return tailwindClasses.join(' ');
}

/**
 * Batch convert multiple elements
 * @param {Object} elementsMap - Object with selectors as keys and Bootstrap classes as values
 * @returns {Object} - Object with selectors as keys and Tailwind classes as values
 */
export function batchConvert(elementsMap) {
    const converted = {};
    
    for (const [selector, bootstrapClasses] of Object.entries(elementsMap)) {
        converted[selector] = convertToTailwind(bootstrapClasses);
    }
    
    return converted;
}

export default { classMap, convertToTailwind, batchConvert };