/**
 * Med Calculators - Frontend Application
 *
 * Handles form submissions, AJAX requests, and result rendering
 * for all medical calculators.
 *
 * @package MedCalculators
 */

(function() {
    'use strict';

    /**
     * MedCalc Application
     */
    const MedCalc = {
        /**
         * Configuration from WordPress
         */
        config: window.medCalcConfig || {},

        /**
         * Initialize the application
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Use event delegation for forms
            document.addEventListener('submit', this.handleFormSubmit.bind(this));
        },

        /**
         * Handle form submission
         *
         * @param {Event} event Submit event
         */
        handleFormSubmit: function(event) {
            const form = event.target;

            // Only handle our calculator forms
            if (!form.classList.contains('med-calc-form')) {
                return;
            }

            event.preventDefault();

            const card = form.closest('.med-calc-card');
            const type = form.dataset.type;
            const button = form.querySelector('.med-btn');

            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Show loading state
            this.setLoading(button, true);
            this.hideResults(card);
            this.hideError(card);

            // Gather form data
            const formData = new FormData(form);
            formData.append('action', 'med_calc');
            formData.append('nonce', this.config.nonce);
            formData.append('type', type);

            // Send AJAX request
            this.sendRequest(formData)
                .then(response => this.handleResponse(response, card, type))
                .catch(error => this.handleError(error, card))
                .finally(() => this.setLoading(button, false));
        },

        /**
         * Send AJAX request
         *
         * @param {FormData} formData Form data
         * @returns {Promise} Response promise
         */
        sendRequest: function(formData) {
            return fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(this.config.i18n.error);
                }
                return response.json();
            });
        },

        /**
         * Handle successful response
         *
         * @param {Object} response Response data
         * @param {Element} card Calculator card element
         * @param {string} type Calculator type
         */
        handleResponse: function(response, card, type) {
            if (!response.success) {
                const message = response.data?.message || this.config.i18n.error;
                this.showError(card, message);
                return;
            }

            // Render results based on calculator type
            this.renderResults(card, response.data, type);
        },

        /**
         * Handle error response
         *
         * @param {Error} error Error object
         * @param {Element} card Calculator card element
         */
        handleError: function(error, card) {
            console.error('Med Calculator Error:', error);
            this.showError(card, error.message || this.config.i18n.error);
        },

        /**
         * Render results to the card
         *
         * @param {Element} card Calculator card element
         * @param {Object} data Response data
         * @param {string} type Calculator type
         */
        renderResults: function(card, data, type) {
            const resultContainer = card.querySelector('.med-result');

            if (!resultContainer) {
                return;
            }

            // Populate simple fields
            this.populateFields(resultContainer, data);

            // Handle type-specific rendering
            switch (type) {
                case 'pregnancy':
                    this.renderPregnancyResults(resultContainer, data);
                    break;
                case 'ovulation':
                    this.renderOvulationResults(resultContainer, data);
                    break;
                case 'calories':
                    this.renderCaloriesResults(resultContainer, data);
                    break;
            }

            // Apply animation class based on settings
            const rootStyles = getComputedStyle(document.documentElement);
            const animationType = rootStyles.getPropertyValue('--med-result-animation').trim() || 'fade';
            
            // Remove all animation classes
            resultContainer.classList.remove('animation-fade', 'animation-slide', 'animation-zoom', 'animation-bounce');
            // Add the selected animation class
            resultContainer.classList.add(`animation-${animationType}`);

            // Show results
            resultContainer.style.display = 'block';
            
            // Scroll to results
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },

        /**
         * Populate simple data fields
         *
         * @param {Element} container Container element
         * @param {Object} data Response data
         */
        populateFields: function(container, data) {
            const fields = container.querySelectorAll('[data-field]');

            fields.forEach(field => {
                const key = field.dataset.field;
                
                if (data.hasOwnProperty(key) && typeof data[key] !== 'object') {
                    field.textContent = data[key];
                }
            });
        },

        /**
         * Render pregnancy-specific results
         *
         * @param {Element} container Result container
         * @param {Object} data Response data
         */
        renderPregnancyResults: function(container, data) {
            // Format trimester
            const trimesterField = container.querySelector('[data-field="trimester"]');
            if (trimesterField && data.trimester) {
                const ordinal = this.getOrdinal(data.trimester);
                trimesterField.textContent = ordinal + ' Trimester';
            }

            // Format current week
            const weekField = container.querySelector('[data-field="current_week"]');
            if (weekField && data.current_week) {
                weekField.textContent = 'Week ' + data.current_week;
            }
        },

        /**
         * Render ovulation-specific results
         *
         * @param {Element} container Result container
         * @param {Object} data Response data
         */
        renderOvulationResults: function(container, data) {
            // Render fertility status
            const statusContainer = container.querySelector('[data-field="fertility_status"]');
            if (statusContainer && data.fertility_status) {
                statusContainer.textContent = data.fertility_status.message;
                statusContainer.dataset.status = data.fertility_status.status;
            }

            // Render fertile days calendar
            const daysContainer = container.querySelector('[data-field="fertile_days"]');
            if (daysContainer && data.fertile_days) {
                daysContainer.innerHTML = data.fertile_days.map(day => {
                    const classes = ['med-fertile-day', 'med-fertile-day--' + day.level];
                    if (day.is_ovulation) {
                        classes.push('med-fertile-day--ovulation');
                    }
                    return `<span class="${classes.join(' ')}">${this.escapeHtml(day.date)}</span>`;
                }).join('');
            }
        },

        /**
         * Render calories-specific results
         *
         * @param {Element} container Result container
         * @param {Object} data Response data
         */
        renderCaloriesResults: function(container, data) {
            // Render BMI category
            const bmiCategoryContainer = container.querySelector('[data-field="bmi_category"]');
            if (bmiCategoryContainer && data.bmi_category) {
                bmiCategoryContainer.textContent = data.bmi_category.label;
                bmiCategoryContainer.style.backgroundColor = this.hexToRgba(data.bmi_category.color, 0.15);
                bmiCategoryContainer.style.color = data.bmi_category.color;
            }

            // Render goals grid
            const goalsContainer = container.querySelector('[data-field="goals"]');
            if (goalsContainer && data.goals) {
                goalsContainer.innerHTML = Object.entries(data.goals).map(([key, goal]) => {
                    const isActive = key === 'maintain';
                    return `
                        <div class="med-goal-item ${isActive ? 'med-goal-item--active' : ''}">
                            <span class="med-goal-calories">${this.formatNumber(goal.calories)}</span>
                            <span class="med-goal-label">${this.escapeHtml(goal.label)}</span>
                        </div>
                    `;
                }).join('');
            }

            // Render macros
            const macrosContainer = container.querySelector('[data-field="macros"]');
            if (macrosContainer && data.macros) {
                macrosContainer.innerHTML = `
                    <div class="med-macro-item">
                        <span class="med-macro-name">Protein</span>
                        <span class="med-macro-value med-macro-value--protein">${data.macros.protein.grams}g</span>
                        <span class="med-macro-unit">${data.macros.protein.percent}%</span>
                    </div>
                    <div class="med-macro-item">
                        <span class="med-macro-name">Carbs</span>
                        <span class="med-macro-value med-macro-value--carbs">${data.macros.carbs.grams}g</span>
                        <span class="med-macro-unit">${data.macros.carbs.percent}%</span>
                    </div>
                    <div class="med-macro-item">
                        <span class="med-macro-name">Fat</span>
                        <span class="med-macro-value med-macro-value--fat">${data.macros.fat.grams}g</span>
                        <span class="med-macro-unit">${data.macros.fat.percent}%</span>
                    </div>
                `;
            }
        },

        /**
         * Set loading state on button
         *
         * @param {Element} button Button element
         * @param {boolean} isLoading Loading state
         */
        setLoading: function(button, isLoading) {
            if (!button) return;

            if (isLoading) {
                button.classList.add('is-loading');
                button.disabled = true;
            } else {
                button.classList.remove('is-loading');
                button.disabled = false;
            }
        },

        /**
         * Hide results container
         *
         * @param {Element} card Calculator card element
         */
        hideResults: function(card) {
            const resultContainer = card.querySelector('.med-result');
            if (resultContainer) {
                resultContainer.style.display = 'none';
            }
        },

        /**
         * Show error message
         *
         * @param {Element} card Calculator card element
         * @param {string} message Error message
         */
        showError: function(card, message) {
            const errorContainer = card.querySelector('.med-error');
            const errorMessage = card.querySelector('.med-error-message');

            if (errorContainer && errorMessage) {
                errorMessage.textContent = message;
                errorContainer.style.display = 'flex';
            }
        },

        /**
         * Hide error message
         *
         * @param {Element} card Calculator card element
         */
        hideError: function(card) {
            const errorContainer = card.querySelector('.med-error');
            if (errorContainer) {
                errorContainer.style.display = 'none';
            }
        },

        /**
         * Get ordinal suffix for number
         *
         * @param {number} n Number
         * @returns {string} Number with ordinal suffix
         */
        getOrdinal: function(n) {
            const s = ['th', 'st', 'nd', 'rd'];
            const v = n % 100;
            return n + (s[(v - 20) % 10] || s[v] || s[0]);
        },

        /**
         * Format number with locale
         *
         * @param {number} n Number to format
         * @returns {string} Formatted number
         */
        formatNumber: function(n) {
            return new Intl.NumberFormat().format(n);
        },

        /**
         * Escape HTML to prevent XSS
         *
         * @param {string} str String to escape
         * @returns {string} Escaped string
         */
        escapeHtml: function(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Convert hex color to rgba
         *
         * @param {string} hex Hex color
         * @param {number} alpha Alpha value
         * @returns {string} RGBA color
         */
        hexToRgba: function(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => MedCalc.init());
    } else {
        MedCalc.init();
    }

    // Expose to window for debugging (optional)
    window.MedCalc = MedCalc;

})();

