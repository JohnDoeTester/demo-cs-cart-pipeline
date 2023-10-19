export const Tygh = {
    embedded: typeof (TYGH_LOADER) !== 'undefined',
    doc: typeof (TYGH_LOADER) !== 'undefined' ? TYGH_LOADER.doc : document,
    body: typeof (TYGH_LOADER) !== 'undefined' ? TYGH_LOADER.body : null, // will be defined in runCart method
    otherjQ: typeof (TYGH_LOADER) !== 'undefined' && TYGH_LOADER.otherjQ,
    facebook: typeof (TYGH_FACEBOOK) !== 'undefined' && TYGH_FACEBOOK,
    container: 'tygh_main_container',
    init_container: 'tygh_container',
    area: '',
    security_hash: '',
    isTouch: false,
    anchor: typeof (TYGH_LOADER) !== 'undefined' ? '' : window.location.hash
};

// TODO: Move functions below to another module

/**
 * @description Language variables storage
 * @type {Map<string,string>}
 */
export const lang = {};

/**
 * Get or set language variable. Btw, should store objects.
 * @param {string} name Language variable id
 * @param {string|Object} value Language variable value
 * @returns {boolean|string|Object}
 */
export const tr = function (name, value) {
    const $ = Tygh.$;
    if (typeof (name) == 'string' && typeof (value) == 'undefined') {
        if (!lang[name]) { console.error(`'${name}' is not defined`); }
        return lang[name];
    } else if (typeof (value) != 'undefined') {
        lang[name] = value;
        return true;
    } else if (typeof (name) == 'object') {
        $.extend(lang, name);
        return true;
    }

    return false;
}

Tygh.tr = tr;
Tygh.lang = lang;

Tygh.toNumeric = function (arg) {
    var number = Number(String(arg).str_replace(',', '.'));

    return isNaN(number) ? 0 : number;
};

/**
 * Returns number of signs after comma of given float
 *
 * @param {number}
 * @returns {number}
 */
Tygh.getFloatPrecision = function (x) {
    return String(x).replace('.', '').length - x.toFixed().length;
};
