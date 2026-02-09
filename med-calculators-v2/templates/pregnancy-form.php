<?php
/**
 * Pregnancy Calculator Form Template
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="med-calc-card" data-calculator="pregnancy">
    <div class="med-calc-header">
        <div class="med-calc-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="4"/>
                <path d="M12 12c-4 0-6 2-6 6v2h12v-2c0-4-2-6-6-6z"/>
                <path d="M12 14v4"/>
            </svg>
        </div>
        <div class="med-calc-title-group">
            <h3 class="med-calc-title"><?php esc_html_e( 'Pregnancy Due Date Calculator', 'med-calculators' ); ?></h3>
            <p class="med-calc-subtitle"><?php esc_html_e( 'Calculate your estimated due date based on your last menstrual period', 'med-calculators' ); ?></p>
        </div>
    </div>

    <form class="med-calc-form" data-type="pregnancy">
        <div class="med-form-group">
            <label class="med-label" for="pregnancy-lmp">
                <?php esc_html_e( 'First Day of Last Menstrual Period', 'med-calculators' ); ?>
                <span class="med-required">*</span>
            </label>
            <input 
                type="date" 
                id="pregnancy-lmp" 
                name="lmp" 
                class="med-input" 
                required
                max="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
            >
            <span class="med-hint"><?php esc_html_e( 'Select the first day of your last period', 'med-calculators' ); ?></span>
        </div>

        <button type="submit" class="med-btn med-btn-primary">
            <span class="med-btn-text"><?php esc_html_e( 'Calculate Due Date', 'med-calculators' ); ?></span>
            <span class="med-btn-loader"></span>
        </button>
    </form>

    <div class="med-result" style="display: none;">
        <div class="med-result-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span><?php esc_html_e( 'Your Results', 'med-calculators' ); ?></span>
        </div>
        
        <div class="med-result-content">
            <div class="med-result-primary">
                <span class="med-result-label"><?php esc_html_e( 'Expected Due Date', 'med-calculators' ); ?></span>
                <span class="med-result-value" data-field="due_date"></span>
            </div>
            
            <div class="med-result-grid">
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Conception Date', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="conception_date"></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Current Week', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="current_week"></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Trimester', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="trimester"></span>
                </div>
                <div class="med-result-item">
                    <span class="med-result-item-label"><?php esc_html_e( 'Days Remaining', 'med-calculators' ); ?></span>
                    <span class="med-result-item-value" data-field="days_remaining"></span>
                </div>
            </div>
        </div>

        <div class="med-result-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <p><?php esc_html_e( 'This is an estimated date. Only about 5% of babies are born on their exact due date. Consult your healthcare provider for personalized advice.', 'med-calculators' ); ?></p>
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

