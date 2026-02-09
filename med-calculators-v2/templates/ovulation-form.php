<?php
/**
 * Ovulation Calculator Form Template
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="med-calc-card" data-calculator="ovulation">
    <div class="med-calc-header">
        <div class="med-calc-icon med-calc-icon--ovulation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
                <circle cx="12" cy="16" r="2"/>
            </svg>
        </div>
        <div class="med-calc-title-group">
            <h3 class="med-calc-title"><?php esc_html_e( 'Ovulation Calculator', 'med-calculators' ); ?></h3>
            <p class="med-calc-subtitle"><?php esc_html_e( 'Find your fertile window and ovulation date', 'med-calculators' ); ?></p>
        </div>
    </div>

    <form class="med-calc-form" data-type="ovulation">
        <div class="med-form-group">
            <label class="med-label" for="ovulation-lmp">
                <?php esc_html_e( 'First Day of Last Period', 'med-calculators' ); ?>
                <span class="med-required">*</span>
            </label>
            <input 
                type="date" 
                id="ovulation-lmp" 
                name="lmp" 
                class="med-input" 
                required
            >
            <span class="med-hint"><?php esc_html_e( 'When did your last period start?', 'med-calculators' ); ?></span>
        </div>

        <div class="med-form-group">
            <label class="med-label" for="ovulation-cycle">
                <?php esc_html_e( 'Average Cycle Length', 'med-calculators' ); ?>
                <span class="med-required">*</span>
            </label>
            <div class="med-input-with-suffix">
                <input 
                    type="number" 
                    id="ovulation-cycle" 
                    name="cycle_length" 
                    class="med-input" 
                    min="21" 
                    max="35" 
                    value="28"
                    required
                >
                <span class="med-input-suffix"><?php esc_html_e( 'days', 'med-calculators' ); ?></span>
            </div>
            <span class="med-hint"><?php esc_html_e( 'Typical cycle length is 21-35 days', 'med-calculators' ); ?></span>
        </div>

        <button type="submit" class="med-btn med-btn-primary">
            <span class="med-btn-text"><?php esc_html_e( 'Calculate Ovulation', 'med-calculators' ); ?></span>
            <span class="med-btn-loader"></span>
        </button>
    </form>

    <div class="med-result" style="display: none;">
        <div class="med-result-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span><?php esc_html_e( 'Your Fertility Window', 'med-calculators' ); ?></span>
        </div>
        
        <div class="med-result-content">
            <div class="med-result-primary med-result-primary--ovulation">
                <span class="med-result-label"><?php esc_html_e( 'Estimated Ovulation Date', 'med-calculators' ); ?></span>
                <span class="med-result-value" data-field="ovulation_date"></span>
            </div>

            <div class="med-fertility-status" data-field="fertility_status"></div>
            
            <div class="med-result-grid">
                <div class="med-result-item med-result-item--highlight">
                    <span class="med-result-item-label"><?php esc_html_e( 'Fertile Window', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="fertile_window"></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Peak Fertility', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="peak_fertility"></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Next Period', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="next_period"></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Cycle Length', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value"><span data-field="cycle_length"></span> <?php esc_html_e( 'days', 'med-calculators' ); ?></span>
                </div>
            </div>

            <div class="med-fertile-days">
                <h4 class="med-fertile-days-title"><?php esc_html_e( 'Fertility Calendar', 'med-calculators' ); ?></h4>
                <div class="med-fertile-days-list" data-field="fertile_days"></div>
                <div class="med-fertile-legend">
                    <span class="med-legend-item med-legend-item--high"><?php esc_html_e( 'High', 'med-calculators' ); ?></span>
                    <span class="med-legend-item med-legend-item--medium"><?php esc_html_e( 'Medium', 'med-calculators' ); ?></span>
                    <span class="med-legend-item med-legend-item--low"><?php esc_html_e( 'Low', 'med-calculators' ); ?></span>
                </div>
            </div>
        </div>

        <div class="med-result-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <p><?php esc_html_e( 'These dates are estimates based on your average cycle length. Individual cycles may vary. For family planning, consult a healthcare provider.', 'med-calculators' ); ?></p>
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

