/**
 * Currency Utility Module
 * Handles all currency formatting and conversion
 */

class CurrencyUtils {
    constructor() {
        this.baseCurrency = 'USD'; // Always use USD as base for storage
        this.defaultCurrency = 'PHP'; // Display currency
        this.locale = 'en-PH';
        this.loadSettings();
    }

    /**
     * Load currency settings from localStorage or use defaults
     */
    loadSettings() {
        const settings = localStorage.getItem('accountingSettings');
        if (settings) {
            try {
                const parsed = JSON.parse(settings);
                if (parsed.general?.defaultCurrency) {
                    this.setCurrency(parsed.general.defaultCurrency);
                }
            } catch (e) {
                console.warn('Failed to parse currency settings:', e);
                // Set default to PHP if parsing fails
                this.setCurrency('PHP');
            }
        } else {
            // Set default to PHP if no settings exist
            this.setCurrency('PHP');
        }
    }

    /**
     * Set currency and update locale accordingly
     */
    setCurrency(currencyCode) {
        this.defaultCurrency = currencyCode;
        
        // Map currency codes to appropriate locales
        const localeMap = {
            'PHP': 'en-PH',
            'USD': 'en-US',
            'EUR': 'de-DE',
            'GBP': 'en-GB',
            'CAD': 'en-CA',
            'AUD': 'en-AU',
            'JPY': 'ja-JP',
            'CNY': 'zh-CN'
        };
        
        this.locale = localeMap[currencyCode] || 'en-US';
        console.log(`Currency set to ${currencyCode} with locale ${this.locale}`);
    }

    /**
     * Get exchange rates (USD as base)
     */
    getExchangeRates() {
        return {
            'USD': 1.0,
            'PHP': 58.5,  // 1 USD = 58.5 PHP (approximate)
            'EUR': 0.92,  // 1 USD = 0.92 EUR
            'GBP': 0.79,  // 1 USD = 0.79 GBP
            'CAD': 1.36,  // 1 USD = 1.36 CAD
            'AUD': 1.52,  // 1 USD = 1.52 AUD
            'JPY': 149.5, // 1 USD = 149.5 JPY
            'CNY': 7.24   // 1 USD = 7.24 CNY
        };
    }

    /**
     * Convert value from base currency (USD) to target currency
     */
    convertFromBase(value, targetCurrency) {
        const rates = this.getExchangeRates();
        const rate = rates[targetCurrency] || 1.0;
        return value * rate;
    }

    /**
     * Convert value to base currency (USD) from source currency
     */
    convertToBase(value, sourceCurrency) {
        const rates = this.getExchangeRates();
        const rate = rates[sourceCurrency] || 1.0;
        return value / rate;
    }

    /**
     * Format currency value with current settings (assumes input is in USD base)
     */
    formatCurrency(value, currencyCode = null) {
        const currency = currencyCode || this.defaultCurrency;
        const locale = currencyCode ? 
            (this.getCurrencyLocale(currencyCode) || 'en-US') : 
            this.locale;
        
        // Convert from USD base to target currency
        const convertedValue = this.convertFromBase(value, currency);
        
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(convertedValue);
    }

    /**
     * Get locale for specific currency
     */
    getCurrencyLocale(currencyCode) {
        const localeMap = {
            'PHP': 'en-PH',
            'USD': 'en-US',
            'EUR': 'de-DE',
            'GBP': 'en-GB',
            'CAD': 'en-CA',
            'AUD': 'en-AU',
            'JPY': 'ja-JP',
            'CNY': 'zh-CN'
        };
        
        return localeMap[currencyCode];
    }

    /**
     * Get currency symbol for specific currency
     */
    getCurrencySymbol(currencyCode = null) {
        const currency = currencyCode || this.defaultCurrency;
        
        // Format 0 to get the symbol
        const formatted = this.formatCurrency(0, currency);
        return formatted.replace(/[0-9.,\s]/g, '');
    }

    /**
     * Get current currency code
     */
    getCurrentCurrency() {
        return this.defaultCurrency;
    }

    /**
     * Get available currencies
     */
    getAvailableCurrencies() {
        return [
            { code: 'PHP', name: 'Philippine Peso', symbol: '₱' },
            { code: 'USD', name: 'US Dollar', symbol: '$' },
            { code: 'EUR', name: 'Euro', symbol: '€' },
            { code: 'GBP', name: 'British Pound', symbol: '£' },
            { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$' },
            { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
            { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
            { code: 'CNY', name: 'Chinese Yuan', symbol: '¥' }
        ];
    }

    /**
     * Update currency settings and save to localStorage
     */
    updateCurrencySettings(currencyCode) {
        console.log('Updating currency settings to:', currencyCode);
        this.setCurrency(currencyCode);
        
        // Update localStorage
        const settings = localStorage.getItem('accountingSettings');
        let parsedSettings = {};
        
        if (settings) {
            try {
                parsedSettings = JSON.parse(settings);
            } catch (e) {
                console.warn('Failed to parse existing settings:', e);
            }
        }
        
        if (!parsedSettings.general) {
            parsedSettings.general = {};
        }
        
        parsedSettings.general.defaultCurrency = currencyCode;
        localStorage.setItem('accountingSettings', JSON.stringify(parsedSettings));
        
        console.log('Currency settings updated, triggering currencyChanged event');
        // Trigger currency change event
        window.dispatchEvent(new CustomEvent('currencyChanged', { 
            detail: { currency: currencyCode } 
        }));
    }
}

// Create singleton instance
const currencyUtils = new CurrencyUtils();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = currencyUtils;
} else {
    window.currencyUtils = currencyUtils;
}