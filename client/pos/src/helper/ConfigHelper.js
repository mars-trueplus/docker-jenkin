import Config from '../config/Config'
import ConfigConstant from "../view/constant/ConfigConstant";

export default {
    // eslint-disable-next-line
    regexEmail: /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i,
    /**
     * Get config by path
     *
     * @param path
     * @returns {null}
     */
    getConfig(path) {
        let config = Config.config.settings.find(item => item.path === path);
        return config ? config.value : null;
    },

    /**
     * can show store credit
     * @returns {boolean}
     */
    isEnableStoreCredit() {
        let isEnable = this.getConfig('customercredit/general/enable');
        return (isEnable && isEnable === '1');
    },

    /**
     * is spent shipping store credit
     * @returns {boolean}
     */
    isSpentCreditOnShippingFee() {
        let isSpentCreditOnShippingFee = this.getConfig('customercredit/spend/shipping');
        return !!(isSpentCreditOnShippingFee && isSpentCreditOnShippingFee === '1');
    },

    /**
     * Sort array object by array condition fields
     *
     * @param arrayObject
     * @param arraySortFiels
     * @param dirrection
     * @return {*}
     */
    sortArrayObjectsByArrayFields(arrayObject, arraySortFiels, dirrection = 'ASC') {
        return arrayObject.sort((a, b) => this.sortByArrayFields(a, b, arraySortFiels, dirrection));
    },

    /**
     * Sort array object by array condition fields
     *
     * @param a
     * @param b
     * @param arraySortFiels
     * @param index
     * @param direction
     * @return {number}
     */
    sortByArrayFields(a, b, arraySortFiels, direction = 'ASC', index = 0) {
        if (a[arraySortFiels[index]] === b[arraySortFiels[index]]) {
            return this.sortByArrayFields(a, b, arraySortFiels, direction, index + 1);
        } else {
            if (a[arraySortFiels[index]] > b[arraySortFiels[index]]) {
                return direction === "ASC" ? 1 : -1;
            }
            return direction === "ASC" ? -1 : 1;
        }
    },

    isShowReasonOnReceipt() {
        let isEnable = this.getConfig('webpos/custom_receipt/display_reason');
        return !!(isEnable && isEnable === '1');
    },

    /**
     * get locale code
     * @returns {*|null}
     */
    getLocaleCode() {
        let localeCode = this.getConfig(ConfigConstant.CONFIG_XML_PATH_GENERAL_LOCALE_CODE);
        if (!localeCode)
            localeCode = "en_US";
        return localeCode
    }
}