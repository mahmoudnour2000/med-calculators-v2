/**
 * Med Calculators - Admin JavaScript
 *
 * @package MedCalculators
 */

(function() {
    'use strict';

    /**
     * Admin Handler Class
     */
    class MedCalcAdmin {
        constructor() {
            this.init();
        }

        /**
         * Initialize
         */
        init() {
            this.bindCopyButtons();
            this.bindColorPicker();
            this.initLivePreview();
        }

        /**
         * Bind copy to clipboard buttons
         */
        bindCopyButtons() {
            const copyButtons = document.querySelectorAll('.med-calc-copy-shortcode, .med-calc-copy-btn');

            copyButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const textToCopy = button.getAttribute('data-copy');
                    
                    this.copyToClipboard(textToCopy).then(() => {
                        // Visual feedback
                        const originalText = button.innerHTML;
                        button.innerHTML = '<span class="dashicons dashicons-yes"></span> Copied!';
                        button.classList.add('copied');

                        // Reset after 2 seconds
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('copied');
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy:', err);
                    });
                });
            });
        }

        /**
         * Copy text to clipboard
         * 
         * @param {string} text Text to copy
         * @returns {Promise}
         */
        copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                return new Promise((resolve, reject) => {
                    if (document.execCommand('copy')) {
                        resolve();
                    } else {
                        reject();
                    }
                    textArea.remove();
                });
            }
        }

        /**
         * Bind color picker updates
         */
        bindColorPicker() {
            const colorPickers = document.querySelectorAll('input[type="color"]');

            colorPickers.forEach(picker => {
                picker.addEventListener('input', (e) => {
                    const valueDisplay = picker.nextElementSibling;
                    if (valueDisplay && valueDisplay.tagName === 'SPAN') {
                        valueDisplay.textContent = e.target.value;
                    }
                });
            });
        }


    /**
     * Initialize Live Preview functionality
     */
    initLivePreview() {
        // Check if we're on the preview page
        const previewContainer = document.getElementById('preview-container');
        if (!previewContainer) return;

        this.previewFrame = document.getElementById('preview-frame');
        this.customStyles = document.getElementById('mcm-preview-styles');
        
        // Show result in preview for testing
        this.showPreviewResult();
        
        // Initialize hover effects
        this.updateHoverEffects();
        
        this.bindPreviewTabs();
        this.bindPreviewControls();
        this.bindDeviceToggle();
        this.bindSaveButton();
        this.bindResetButton();
        this.bindAccordion();
        this.bindExportCSV();
        this.bindDeleteLog();
    }

    /**
     * Show result in preview for testing
     */
    showPreviewResult() {
        if (!this.previewFrame) return;

        // Bind calculate buttons in preview
        this.bindPreviewCalculate();
    }

    /**
     * Bind calculate button in preview
     */
    bindPreviewCalculate() {
        const calcForms = this.previewFrame.querySelectorAll('.mcm-form');
        
        calcForms.forEach(form => {
            const calcBtn = form.querySelector('.mcm-calc-btn');
            if (!calcBtn) return;

            calcBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const root = form.closest('.mcm');
                if (!root) return;

                // Hide placeholder, show result
                const placeholder = root.querySelector('.mcm-placeholder');
                const result = root.querySelector('.mcm-result');
                
                if (placeholder) placeholder.style.display = 'none';
                if (result) {
                    result.style.display = 'flex';
                    
                    // Populate with sample data for calories calculator
                    const type = root.getAttribute('data-calculator');
                    if (type === 'calories') {
                        this.populateCaloriesResult(root);
                        this.bindCaloriesInteractions(root);
                    } else if (type === 'ovulation') {
                        this.populateOvulationResult(root);
                    } else if (type === 'pregnancy') {
                        this.populatePregnancyResult(root);
                    }
                }
            });

            // Bind clear button
            const clearBtn = form.querySelector('.mcm-clear-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    form.reset();
                    
                    const root = form.closest('.mcm');
                    const placeholder = root.querySelector('.mcm-placeholder');
                    const result = root.querySelector('.mcm-result');
                    
                    if (placeholder) placeholder.style.display = '';
                    if (result) result.style.display = 'none';
                });
            }
        });
    }

    /**
     * Bind calories calculator interactions (tabs, protein slider)
     */
    bindCaloriesInteractions(root) {
        // Bind meal tabs
        const tabs = root.querySelectorAll('.mcm-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('mcm-tab--active'));
                tab.classList.add('mcm-tab--active');
                
                const meals = parseInt(tab.getAttribute('data-meals'));
                const carbsEl = root.querySelector('#mcm-macro-carbs');
                const proteinEl = root.querySelector('#mcm-macro-protein');
                const fatEl = root.querySelector('#mcm-macro-fat');
                
                if (meals === 1) {
                    if (carbsEl) carbsEl.textContent = '270g/40%';
                    if (proteinEl) proteinEl.textContent = '203g/30%';
                    if (fatEl) fatEl.textContent = '90g/30%';
                } else {
                    const carbsPerMeal = Math.round(270 / meals);
                    const proteinPerMeal = Math.round(203 / meals);
                    const fatPerMeal = Math.round(90 / meals);
                    
                    if (carbsEl) carbsEl.textContent = carbsPerMeal + 'g/meal';
                    if (proteinEl) proteinEl.textContent = proteinPerMeal + 'g/meal';
                    if (fatEl) fatEl.textContent = fatPerMeal + 'g/meal';
                }
            });
        });

        // Bind protein slider
        const proteinSlider = root.querySelector('#mcm-protein-slider');
        const proteinLabels = root.querySelectorAll('.mcm-protein-label');
        
        if (proteinSlider && proteinLabels.length) {
            proteinSlider.addEventListener('input', () => {
                const level = parseInt(proteinSlider.value);
                proteinLabels.forEach((label, i) => {
                    label.classList.toggle('mcm-protein-label--active', i === level);
                });
                
                // Recalculate macros based on protein level
                this.recalculateMacros(root, level);
            });

            proteinLabels.forEach((label, i) => {
                label.addEventListener('click', () => {
                    proteinSlider.value = i;
                    proteinLabels.forEach((l, j) => {
                        l.classList.toggle('mcm-protein-label--active', j === i);
                    });
                    this.recalculateMacros(root, i);
                });
            });
        }
    }

    /**
     * Recalculate macros based on protein level
     */
    recalculateMacros(root, level) {
        const calories = 1890;
        const splits = [
            { p: 0.20, c: 0.50, f: 0.30 },  // Low
            { p: 0.30, c: 0.40, f: 0.30 },  // Normal
            { p: 0.40, c: 0.35, f: 0.25 }   // High
        ];
        
        const split = splits[level] || splits[1];
        
        const carbs = Math.round((calories * split.c) / 4);
        const protein = Math.round((calories * split.p) / 4);
        const fat = Math.round((calories * split.f) / 9);
        
        const carbsPercent = Math.round(split.c * 100);
        const proteinPercent = Math.round(split.p * 100);
        const fatPercent = Math.round(split.f * 100);
        
        const carbsEl = root.querySelector('#mcm-macro-carbs');
        const proteinEl = root.querySelector('#mcm-macro-protein');
        const fatEl = root.querySelector('#mcm-macro-fat');
        
        if (carbsEl) carbsEl.textContent = carbs + 'g/' + carbsPercent + '%';
        if (proteinEl) proteinEl.textContent = protein + 'g/' + proteinPercent + '%';
        if (fatEl) fatEl.textContent = fat + 'g/' + fatPercent + '%';
        
        // Reset tabs to "PER DAY"
        const tabs = root.querySelectorAll('.mcm-tab');
        tabs.forEach(t => t.classList.toggle('mcm-tab--active', t.getAttribute('data-meals') === '1'));
    }

    /**
     * Populate calories result with sample data
     */
    populateCaloriesResult(root) {
        const kcalNumber = root.querySelector('#mcm-kcal-number');
        const carbsMacro = root.querySelector('#mcm-macro-carbs');
        const proteinMacro = root.querySelector('#mcm-macro-protein');
        const fatMacro = root.querySelector('#mcm-macro-fat');

        // Animate number count-up
        if (kcalNumber) {
            let start = 0;
            const end = 1890;
            const duration = 700;
            const startTime = performance.now();
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(start + (end - start) * easeOut);
                kcalNumber.textContent = current;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        }
        
        if (carbsMacro) carbsMacro.textContent = '270g/40%';
        if (proteinMacro) proteinMacro.textContent = '203g/30%';
        if (fatMacro) fatMacro.textContent = '90g/30%';
    }

    /**
     * Populate ovulation result with sample data
     */
    populateOvulationResult(root) {
        const ovulationDate = root.querySelector('#mcm-ovulation-date');
        const fertileWindow = root.querySelector('#mcm-fertile-window');
        const peakFertility = root.querySelector('#mcm-peak-fertility');
        const nextPeriod = root.querySelector('#mcm-next-period');

        const today = new Date();
        const ovDate = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000);
        const nextPerDate = new Date(today.getTime() + 28 * 24 * 60 * 60 * 1000);

        if (ovulationDate) ovulationDate.textContent = ovDate.toLocaleDateString();
        if (fertileWindow) fertileWindow.textContent = '5 days';
        if (peakFertility) peakFertility.textContent = ovDate.toLocaleDateString();
        if (nextPeriod) nextPeriod.textContent = nextPerDate.toLocaleDateString();
    }

    /**
     * Populate pregnancy result with sample data
     */
    populatePregnancyResult(root) {
        const dueDate = root.querySelector('#mcm-due-date');
        const currentWeek = root.querySelector('#mcm-current-week');
        const trimester = root.querySelector('#mcm-trimester');
        const daysRemaining = root.querySelector('#mcm-days-remaining');
        const conceptionDate = root.querySelector('#mcm-conception-date');
        const weeksRemaining = root.querySelector('#mcm-weeks-remaining');
        const progressFill = root.querySelector('#mcm-progress-fill');

        const today = new Date();
        const due = new Date(today.getTime() + 200 * 24 * 60 * 60 * 1000);

        if (dueDate) dueDate.textContent = due.toLocaleDateString();
        if (currentWeek) currentWeek.textContent = 'Week 12';
        if (trimester) trimester.textContent = '1st Trimester';
        if (daysRemaining) daysRemaining.textContent = '200 days';
        if (conceptionDate) conceptionDate.textContent = new Date(today.getTime() - 84 * 24 * 60 * 60 * 1000).toLocaleDateString();
        if (weeksRemaining) weeksRemaining.textContent = '28 weeks';
        if (progressFill) progressFill.style.width = '30%';
    }

        /**
         * Bind preview tabs
         */
        bindPreviewTabs() {
            const tabs = document.querySelectorAll('.med-calc-preview-tab');
            const previews = document.querySelectorAll('.med-calc-preview-item');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const calculator = tab.getAttribute('data-calculator');
                    
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Show selected preview
                    previews.forEach(preview => {
                        if (preview.getAttribute('data-preview') === calculator) {
                            preview.style.display = 'block';
                        } else {
                            preview.style.display = 'none';
                        }
                    });
                });
            });
        }

        /**
         * Bind all preview controls
         */
        bindPreviewControls() {
            const controls = document.querySelectorAll('.med-calc-preview-input');
            
            if (!controls.length) return;

            controls.forEach(control => {
                const setting = control.getAttribute('data-setting');
                const type = control.type;

                // Bind input/change event for real-time updates
                let eventType = 'input';
                if (type === 'select-one') {
                    eventType = 'change';
                } else if (type === 'checkbox') {
                    eventType = 'change';
                }
                
                control.addEventListener(eventType, (e) => {
                    const value = type === 'checkbox' ? (e.target.checked ? '1' : '0') : e.target.value;
                    this.updatePreviewSetting(setting, value, control);
                });
            });
        }

    /**
     * Update preview setting
     */
    updatePreviewSetting(setting, value, control) {
        // Update display value
        if (control.type === 'color') {
            const display = control.parentElement.querySelector('.color-value');
            if (display) display.textContent = value;
        } else if (control.type === 'range') {
            const display = control.parentElement.querySelector('.range-value');
            if (display) {
                // Determine unit based on setting name
                let unit = 'px';
                if (setting === 'mcm_input_bg_opacity') {
                    unit = '%';
                }
                display.textContent = value + unit;
            }
        }

        // Handle modern design specific cases
        if (setting === 'mcm_btn_radius') {
            // Apply button radius to specific elements
            const buttons = this.previewFrame.querySelectorAll('.mcm-gender__btn, .mcm-goal, .mcm-calc-btn, .mcm-tab');
            buttons.forEach(btn => {
                btn.style.borderRadius = value + 'px';
            });
        }

        if (setting === 'mcm_btn_padding') {
            // Apply button padding
            const calcBtns = this.previewFrame.querySelectorAll('.mcm-calc-btn');
            calcBtns.forEach(btn => {
                btn.style.paddingTop = value + 'px';
                btn.style.paddingBottom = value + 'px';
            });
        }

        if (setting === 'mcm_slider_thumb_style') {
            // Apply slider thumb style
            const sliders = this.previewFrame.querySelectorAll('.mcm-slider, .mcm-protein-slider');
            const radius = value === 'square' ? '4px' : '50%';
            sliders.forEach(slider => {
                // Note: This won't work directly, needs CSS update
                // Just update the CSS variable
            });
        }

        if (setting === 'mcm_max_width') {
            // Apply max width to wrapper
            const wrappers = this.previewFrame.querySelectorAll('.mcm-wrapper');
            wrappers.forEach(wrapper => {
                wrapper.style.maxWidth = value + 'px';
            });
        }

        if (setting === 'mcm_enable_animations') {
            // Handle animations toggle
            const enabled = value === '1' || value === '1' || control.checked;
            const mcmElements = this.previewFrame.querySelectorAll('.mcm *');
            if (!enabled) {
                mcmElements.forEach(el => {
                    el.style.transitionDuration = '0s';
                    el.style.animation = 'none';
                });
            } else {
                mcmElements.forEach(el => {
                    el.style.transitionDuration = '';
                    el.style.animation = '';
                });
            }
        }

        // Handle hover effects
        if (setting === 'mcm_enable_hover' || setting === 'mcm_hover_color' || 
            setting === 'mcm_hover_brightness' || setting === 'mcm_hover_scale') {
            this.updateHoverEffects();
        }

        // Update preview CSS variables
        this.updateCSSVariable(setting, value);
    }

    /**
     * Update hover effects dynamically
     */
    updateHoverEffects() {
        const enableHover = document.querySelector('[data-setting="mcm_enable_hover"]');
        const hoverColor = document.querySelector('[data-setting="mcm_hover_color"]');
        const hoverBrightness = document.querySelector('[data-setting="mcm_hover_brightness"]');
        const hoverScale = document.querySelector('[data-setting="mcm_hover_scale"]');

        if (!enableHover || !enableHover.checked) return;

        const color = hoverColor ? hoverColor.value : '#000000';
        const brightness = hoverBrightness ? hoverBrightness.value : 90;
        const scale = hoverScale ? hoverScale.value / 100 : 1.02;

        // Update or create hover styles
        let hoverStyleEl = document.getElementById('mcm-hover-styles');
        if (!hoverStyleEl) {
            hoverStyleEl = document.createElement('style');
            hoverStyleEl.id = 'mcm-hover-styles';
            document.head.appendChild(hoverStyleEl);
        }

        hoverStyleEl.textContent = `
            .mcm-calc-btn:hover { 
                background: ${color} !important; 
                filter: brightness(${brightness}%); 
                transform: scale(${scale}); 
            }
            .mcm-gender__btn:hover, .mcm-goal:hover { 
                opacity: 0.85 !important; 
                transform: scale(${scale}); 
            }
            .mcm-tab:hover { 
                transform: scale(${scale}); 
            }
        `;
    }

    /**
     * Update CSS variable in preview
     */
    updateCSSVariable(setting, value) {
        if (!this.customStyles) return;

        // Map setting names to CSS variable names (Modern Design)
        const cssVarMap = {
            'mcm_panel_color': 'mcm-panel-color',
            'mcm_active_color': 'mcm-active-color',
            'mcm_result_bg': 'mcm-result-bg',
            'mcm_text_light': 'mcm-text-light',
            'mcm_text_dark': 'mcm-text-dark',
            'mcm_text_gray': 'mcm-text-gray',
            'mcm_panel_width': 'mcm-panel-width',
            'mcm_panel_padding': 'mcm-panel-padding',
            'mcm_result_padding': 'mcm-result-padding',
            'mcm_border_radius': 'mcm-border-radius',
            'mcm_max_width': 'mcm-max-width',
            'mcm_heading_size': 'mcm-heading-size',
            'mcm_body_size': 'mcm-body-size',
            'mcm_input_size': 'mcm-input-size',
            'mcm_result_number_size': 'mcm-result-number-size',
            'mcm_btn_size': 'mcm-btn-size',
            'mcm_font_family': 'mcm-ff',
            'mcm_shadow': 'mcm-shadow',
            'mcm_btn_radius': 'mcm-btn-radius',
            'mcm_btn_padding': 'mcm-btn-padding',
            'mcm_input_bg_opacity': 'mcm-input-bg',
            'mcm_slider_thumb_style': 'mcm-slider-thumb-style',
            'mcm_enable_animations': 'mcm-enable-animations',
            'mcm_hover_color': 'mcm-hover-color',
            'mcm_hover_brightness': 'mcm-hover-brightness',
            'mcm_hover_scale': 'mcm-hover-scale',
            'mcm_enable_hover': 'mcm-enable-hover'
        };

        const cssVarName = cssVarMap[setting] || setting.replace(/_/g, '-');
        
        // Map settings to CSS variable values
        let cssValue = value;

        // Handle special cases
        if (setting === 'mcm_panel_color') {
            // Also update the light variant
            const hex = value.replace('#', '');
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            const lightValue = `rgba(${r},${g},${b},0.3)`;
            
            // Insert the light variant
            const currentStyles = this.customStyles.textContent;
            if (currentStyles.includes('--mcm-panel-color-light:')) {
                this.customStyles.textContent = currentStyles.replace(
                    /(--mcm-panel-color-light:\s*)[^;]+;/,
                    `$1${lightValue};`
                );
            } else {
                this.customStyles.textContent = currentStyles.replace(
                    /\.mcm\s*\{([^}]*)\}/,
                    `.mcm {$1--mcm-panel-color-light: ${lightValue};}`
                );
            }
        }
        
        if (setting === 'mcm_input_bg_opacity') {
            // Convert percentage to rgba
            const opacity = parseInt(value) / 100;
            cssValue = `rgba(0,0,0,${opacity})`;
        } else if (setting === 'mcm_font_family') {
            const fontMap = {
                'system': "'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif",
                'inter': "'Inter', sans-serif",
                'roboto': "'Roboto', sans-serif",
                'poppins': "'Poppins', sans-serif",
                'opensans': "'Open Sans', sans-serif",
                'montserrat': "'Montserrat', sans-serif"
            };
            cssValue = fontMap[value] || fontMap['system'];
        } else if (setting === 'mcm_shadow') {
            const shadowMap = {
                'none': 'none',
                'sm': '0 2px 10px rgba(0,0,0,0.06)',
                'md': '0 8px 40px rgba(0,0,0,0.12)',
                'lg': '0 12px 60px rgba(0,0,0,0.18)',
                'xl': '0 20px 80px rgba(0,0,0,0.24)'
            };
            cssValue = shadowMap[value] || shadowMap['md'];
        } else if (
            setting.includes('size') || 
            setting.includes('padding') || 
            setting.includes('width') || 
            setting.includes('radius')
        ) {
            // Add 'px' unit for numeric values
            cssValue = value + 'px';
        }

        // Get current styles and update the variable
        const currentStyles = this.customStyles.textContent;
        const regex = new RegExp(`(--${cssVarName}:\\s*)[^;]+;`);

        if (currentStyles.includes(`--${cssVarName}:`)) {
            this.customStyles.textContent = currentStyles.replace(regex, `$1${cssValue};`);
        } else {
            // Add new variable if it doesn't exist
            const mcmRule = currentStyles.match(/\.mcm\s*\{([^}]*)\}/);
            if (mcmRule) {
                const newVar = `--${cssVarName}: ${cssValue};`;
                this.customStyles.textContent = currentStyles.replace(
                    /\.mcm\s*\{([^}]*)\}/,
                    `.mcm {$1${newVar}}`
                );
            }
        }
    }

        /**
         * Bind device toggle
         */
        bindDeviceToggle() {
            const deviceButtons = document.querySelectorAll('.med-calc-device-btn');
            
            if (!deviceButtons.length) return;

            deviceButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const device = btn.getAttribute('data-device');
                    
                    // Update active state
                    deviceButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    // Update frame size
                    this.previewFrame.classList.remove('desktop-view', 'tablet-view', 'mobile-view');
                    this.previewFrame.classList.add(`${device}-view`);
                });
            });
        }

    /**
     * Bind save button
     */
    bindSaveButton() {
        const saveBtn = document.getElementById('preview-save-btn');
        if (!saveBtn) return;

        saveBtn.addEventListener('click', () => {
            // Collect all current settings
            const controls = document.querySelectorAll('.med-calc-preview-input');
            const settings = {};
            
            controls.forEach(control => {
                const setting = control.getAttribute('data-setting');
                let value;
                
                if (control.type === 'checkbox') {
                    value = control.checked ? '1' : '0';
                } else {
                    value = control.value;
                }
                
                settings[setting] = value;
            });

            // Show loading state
            const originalHTML = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation: rotation 1s linear infinite;"></span> Saving...';
            saveBtn.disabled = true;

            // Send AJAX request
            jQuery.ajax({
                url: medCalcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'med_calc_save_preview_settings',
                    nonce: medCalcAdmin.nonce,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        // Show success message
                        saveBtn.innerHTML = '<span class="dashicons dashicons-yes"></span> Saved!';
                        saveBtn.style.backgroundColor = '#00a32a';
                        saveBtn.style.borderColor = '#00a32a';

                        // Reset button after 2 seconds
                        setTimeout(() => {
                            saveBtn.innerHTML = originalHTML;
                            saveBtn.style.backgroundColor = '';
                            saveBtn.style.borderColor = '';
                            saveBtn.disabled = false;
                        }, 2000);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to save settings'));
                        saveBtn.innerHTML = originalHTML;
                        saveBtn.disabled = false;
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error: ' + error);
                    saveBtn.innerHTML = originalHTML;
                    saveBtn.disabled = false;
                }
            });
        });
    }

    /**
     * Bind reset button
     */
    bindResetButton() {
        const resetBtn = document.getElementById('preview-reset-btn');
        if (!resetBtn) return;

        const defaults = {
            'mcm_panel_color': '#F47B4A',
            'mcm_active_color': '#1A1A1A',
            'mcm_result_bg': '#FFFFFF',
            'mcm_text_light': '#FFFFFF',
            'mcm_text_dark': '#1A1A1A',
            'mcm_text_gray': '#888888',
            'mcm_panel_width': 400,
            'mcm_panel_padding': 40,
            'mcm_result_padding': 40,
            'mcm_border_radius': 12,
            'mcm_max_width': 960,
            'mcm_heading_size': 15,
            'mcm_body_size': 12,
            'mcm_input_size': 28,
            'mcm_result_number_size': 56,
            'mcm_btn_size': 13,
            'mcm_font_family': 'system',
            'mcm_shadow': 'md',
            'mcm_btn_radius': 0,
            'mcm_btn_padding': 16,
            'mcm_input_bg_opacity': 18,
            'mcm_slider_thumb_style': 'circle',
            'mcm_enable_animations': true,
            'mcm_hover_color': '#000000',
            'mcm_hover_brightness': 90,
            'mcm_hover_scale': 102,
            'mcm_enable_hover': true
        };

        resetBtn.addEventListener('click', () => {
            // Reset all controls
            const controls = document.querySelectorAll('.med-calc-preview-input');
            
            controls.forEach(control => {
                const setting = control.getAttribute('data-setting');
                const defaultValue = defaults[setting];
                
                if (defaultValue !== undefined) {
                    if (control.type === 'checkbox') {
                        control.checked = defaultValue;
                    } else {
                        control.value = defaultValue;
                    }
                    this.updatePreviewSetting(setting, defaultValue, control);
                }
            });

            // Reset device view
            const deviceButtons = document.querySelectorAll('.med-calc-device-btn');
            deviceButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-device') === 'desktop') {
                    btn.classList.add('active');
                }
            });
            this.previewFrame.classList.remove('tablet-view', 'mobile-view');
            this.previewFrame.classList.add('desktop-view');
        });
    }

    /**
     * Bind export CSV button
     */
    bindExportCSV() {
        const exportBtn = document.getElementById('med-calc-export-csv-btn');
        if (!exportBtn) return;

        exportBtn.addEventListener('click', () => {
            window.location.href = medCalcAdmin.ajaxUrl + '?action=med_calc_export_csv&nonce=' + medCalcAdmin.nonce;
        });
    }

    /**
     * Bind delete log buttons
     */
    bindDeleteLog() {
        const deleteButtons = document.querySelectorAll('.med-calc-delete-log-btn');
        if (!deleteButtons.length) return;

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to delete this log?')) {
                    return;
                }

                const logId = this.getAttribute('data-id');
                
                jQuery.ajax({
                    url: medCalcAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'med_calc_delete_log',
                        nonce: medCalcAdmin.nonce,
                        log_id: logId
                    },
                    success: (response) => {
                        if (response.success) {
                            // Remove row from table
                            this.closest('tr').remove();
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to delete log'));
                        }
                    },
                    error: () => {
                        alert('Network error. Please try again.');
                    }
                });
            });
        });
    }

        /**
         * Bind accordion functionality
         */
        bindAccordion() {
            const postboxes = document.querySelectorAll('.postbox');
            
            postboxes.forEach(postbox => {
                const handle = postbox.querySelector('.handlediv, .hndle');
                
                if (!handle) return;

                handle.addEventListener('click', (e) => {
                    e.preventDefault();
                    postbox.classList.toggle('closed');
                    
                    // Update aria-expanded
                    const handlediv = postbox.querySelector('.handlediv');
                    if (handlediv) {
                        const isExpanded = !postbox.classList.contains('closed');
                        handlediv.setAttribute('aria-expanded', isExpanded);
                    }
                });
            });
        }

        /**
         * Adjust color brightness
         */
        adjustBrightness(hex, percent) {
            hex = hex.replace('#', '');
            
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);
            
            r = Math.min(255, Math.max(0, r + Math.round(r * percent / 100)));
            g = Math.min(255, Math.max(0, g + Math.round(g * percent / 100)));
            b = Math.min(255, Math.max(0, b + Math.round(b * percent / 100)));
            
            return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
        }

        /**
         * Convert hex to rgba
         */
        hexToRgba(hex, alpha) {
            hex = hex.replace('#', '');
            
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        new MedCalcAdmin();
    });

})();

