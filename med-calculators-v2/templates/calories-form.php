<?php
/**
 * Calorie Calculator Form Template
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="med-calc-card" data-calculator="calories">
    <div class="med-calc-header">
        <div class="med-calc-icon med-calc-icon--calories">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2c-2.5 3.5-4 6-4 9a4 4 0 0 0 8 0c0-3-1.5-5.5-4-9z"/>
                <path d="M12 22v-6"/>
                <path d="M9 18h6"/>
            </svg>
        </div>
        <div class="med-calc-title-group">
            <h3 class="med-calc-title"><?php esc_html_e( 'Daily Calorie Calculator', 'med-calculators' ); ?></h3>
            <p class="med-calc-subtitle"><?php esc_html_e( 'Calculate your daily calorie needs using the Mifflin-St Jeor equation', 'med-calculators' ); ?></p>
        </div>
    </div>

    <form class="med-calc-form" data-type="calories">
        <div class="med-form-row">
            <div class="med-form-group med-form-group--half">
                <label class="med-label">
                    <?php esc_html_e( 'Gender', 'med-calculators' ); ?>
                    <span class="med-required">*</span>
                </label>
                <div class="med-radio-group">
                    <label class="med-radio">
                        <input type="radio" name="gender" value="male" required>
                        <span class="med-radio-mark"></span>
                        <span class="med-radio-label"><?php esc_html_e( 'Male', 'med-calculators' ); ?></span>
                    </label>
                    <label class="med-radio">
                        <input type="radio" name="gender" value="female" required>
                        <span class="med-radio-mark"></span>
                        <span class="med-radio-label"><?php esc_html_e( 'Female', 'med-calculators' ); ?></span>
                    </label>
                </div>
            </div>

            <div class="med-form-group med-form-group--half">
                <label class="med-label" for="calories-age">
                    <?php esc_html_e( 'Age', 'med-calculators' ); ?>
                    <span class="med-required">*</span>
                </label>
                <div class="med-input-with-suffix">
                    <input 
                        type="number" 
                        id="calories-age" 
                        name="age" 
                        class="med-input" 
                        min="15" 
                        max="120"
                        placeholder="25"
                        required
                    >
                    <span class="med-input-suffix"><?php esc_html_e( 'years', 'med-calculators' ); ?></span>
                </div>
            </div>
        </div>

        <div class="med-form-row">
            <div class="med-form-group med-form-group--half">
                <label class="med-label" for="calories-weight">
                    <?php esc_html_e( 'Weight', 'med-calculators' ); ?>
                    <span class="med-required">*</span>
                </label>
                <div class="med-input-with-suffix">
                    <input 
                        type="number" 
                        id="calories-weight" 
                        name="weight" 
                        class="med-input" 
                        min="30" 
                        max="300"
                        step="0.1"
                        placeholder="70"
                        required
                    >
                    <span class="med-input-suffix"><?php esc_html_e( 'kg', 'med-calculators' ); ?></span>
                </div>
            </div>

            <div class="med-form-group med-form-group--half">
                <label class="med-label" for="calories-height">
                    <?php esc_html_e( 'Height', 'med-calculators' ); ?>
                    <span class="med-required">*</span>
                </label>
                <div class="med-input-with-suffix">
                    <input 
                        type="number" 
                        id="calories-height" 
                        name="height" 
                        class="med-input" 
                        min="100" 
                        max="250"
                        placeholder="175"
                        required
                    >
                    <span class="med-input-suffix"><?php esc_html_e( 'cm', 'med-calculators' ); ?></span>
                </div>
            </div>
        </div>

        <div class="med-form-group">
            <label class="med-label" for="calories-activity">
                <?php esc_html_e( 'Activity Level', 'med-calculators' ); ?>
                <span class="med-required">*</span>
            </label>
            <select id="calories-activity" name="activity" class="med-input med-select" required>
                <option value=""><?php esc_html_e( 'Select your activity level', 'med-calculators' ); ?></option>
                <option value="sedentary"><?php esc_html_e( 'Sedentary (little or no exercise)', 'med-calculators' ); ?></option>
                <option value="light"><?php esc_html_e( 'Lightly Active (light exercise 1-3 days/week)', 'med-calculators' ); ?></option>
                <option value="moderate"><?php esc_html_e( 'Moderately Active (moderate exercise 3-5 days/week)', 'med-calculators' ); ?></option>
                <option value="active"><?php esc_html_e( 'Very Active (hard exercise 6-7 days/week)', 'med-calculators' ); ?></option>
                <option value="very_active"><?php esc_html_e( 'Extra Active (very hard exercise & physical job)', 'med-calculators' ); ?></option>
            </select>
        </div>

        <button type="submit" class="med-btn med-btn-primary">
            <span class="med-btn-text"><?php esc_html_e( 'Calculate Calories', 'med-calculators' ); ?></span>
            <span class="med-btn-loader"></span>
        </button>
    </form>

    <div class="med-result" style="display: none;">
        <div class="med-result-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span><?php esc_html_e( 'Your Daily Calorie Needs', 'med-calculators' ); ?></span>
        </div>
        
        <div class="med-result-content">
            <div class="med-result-primary med-result-primary--calories">
                <span class="med-result-label"><?php esc_html_e( 'Daily Calories to Maintain Weight', 'med-calculators' ); ?></span>
                <span class="med-result-value"><span data-field="tdee_formatted"></span> <small><?php esc_html_e( 'kcal/day', 'med-calculators' ); ?></small></span>
            </div>

            <div class="med-bmi-display">
                <div class="med-bmi-value">
                    <span class="med-bmi-number" data-field="bmi"></span>
                    <span class="med-bmi-label"><?php esc_html_e( 'BMI', 'med-calculators' ); ?></span>
                </div>
                <div class="med-bmi-category" data-field="bmi_category"></div>
            </div>
            
            <div class="med-result-grid med-result-grid--stats">
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Basal Metabolic Rate', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value"><span data-field="bmr_formatted"></span> <?php esc_html_e( 'kcal', 'med-calculators' ); ?></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Activity Level', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="activity_level"></span>
                </div>
            </div>

            <div class="med-goals-section">
                <h4 class="med-goals-title"><?php esc_html_e( 'Calorie Goals', 'med-calculators' ); ?></h4>
                <div class="med-goals-grid" data-field="goals"></div>
            </div>

            <div class="med-macros-section">
                <h4 class="med-macros-title"><?php esc_html_e( 'Recommended Macros (Balanced)', 'med-calculators' ); ?></h4>
                <div class="med-macros-grid" data-field="macros"></div>
            </div>
        </div>

        <div class="med-result-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <p><?php esc_html_e( 'These calculations are estimates based on the Mifflin-St Jeor equation. Individual needs may vary. Consult a healthcare provider or registered dietitian for personalized advice.', 'med-calculators' ); ?></p>
        </div>
    </div>

    <div class="med-error" style="display: none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <span class="med-error-message"></span>
    </div>
</div>

