<?php
/**
 * Modern Ovulation Calculator Template
 * Fully translatable
 *
 * @package MedCalculators
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings   = get_option( 'med_calc_settings', array() );
$disclaimer = isset( $settings['disclaimer_text'] ) && ! empty( $settings['disclaimer_text'] )
    ? $settings['disclaimer_text']
    : __( 'This calculator is for informational purposes only and should not replace professional medical advice.', 'med-calculators' );

// Cache-busting attributes for Elementor (forces cache refresh on language/version change)
$current_locale = get_locale();
?>

<div class="mcm" data-calculator="ovulation" data-locale="<?php echo esc_attr( $current_locale ); ?>" data-version="<?php echo esc_attr( MED_CALC_VERSION ); ?>">
    <div class="mcm-wrapper">

        <!-- ====== LEFT: Form Panel ====== -->
        <div class="mcm-form-panel">
            <form class="mcm-form" data-calculator-type="ovulation">

                <div class="mcm-block">
                    <h3 class="mcm-block__title"><?php esc_html_e( 'Cycle Information', 'med-calculators' ); ?></h3>

                    <!-- Last Period Date -->
                    <div class="mcm-field">
                        <label class="mcm-field__label"><?php esc_html_e( 'First Day of Last Period', 'med-calculators' ); ?></label>
                        <div class="mcm-input-box mcm-input-box--wide">
                            <input type="date" name="lmp" class="mcm-input-box__field mcm-input-box__field--date" required>
                        </div>
                    </div>

                    <!-- Cycle Length -->
                    <div class="mcm-field">
                        <label class="mcm-field__label"><?php esc_html_e( 'Average Cycle Length', 'med-calculators' ); ?></label>
                        <p class="mcm-block__desc"><?php esc_html_e( 'The average menstrual cycle is 28 days, but it can range from 21 to 35 days.', 'med-calculators' ); ?></p>
                        <div class="mcm-slider-wrap">
                            <input type="range" min="21" max="35" value="28" step="1" class="mcm-slider" id="mcm-cycle-slider">
                        </div>
                        <div class="mcm-slider-value-display">
                            <span class="mcm-slider-value" id="mcm-cycle-value">28</span>
                            <span class="mcm-slider-value-label"><?php esc_html_e( 'days', 'med-calculators' ); ?></span>
                        </div>
                        <input type="hidden" name="cycle_length" value="28" id="mcm-cycle-hidden">
                    </div>
                </div>

                <!-- Bottom Actions -->
                <div class="mcm-bottom">
                    <button type="button" class="mcm-clear-btn"><?php esc_html_e( 'CLEAR', 'med-calculators' ); ?></button>
                    <button type="submit" class="mcm-calc-btn"><?php esc_html_e( 'CALCULATE', 'med-calculators' ); ?></button>
                </div>

                <div class="mcm-loader" style="display:none;">
                    <div class="mcm-loader__spinner"></div>
                </div>
            </form>
        </div>

        <!-- ====== RIGHT: Results Panel ====== -->
        <div class="mcm-result-panel">

            <div class="mcm-placeholder" id="mcm-placeholder">
                <h2 class="mcm-result__heading"><?php esc_html_e( 'Your Result', 'med-calculators' ); ?></h2>
                <div class="mcm-placeholder__inner">
                    <p><?php esc_html_e( 'Enter your cycle information and press Calculate to see your fertility window.', 'med-calculators' ); ?></p>
                </div>
            </div>

            <div class="mcm-result" id="mcm-result" style="display:none;">
                <h2 class="mcm-result__heading"><?php esc_html_e( 'Your Result', 'med-calculators' ); ?></h2>

                <div class="mcm-result-top">
                    <div class="mcm-result-top__left">
                        <div class="mcm-kcal">
                            <span class="mcm-kcal__number" id="mcm-ovulation-date">&mdash;</span>
                        </div>
                        <div class="mcm-kcal__sub"><?php esc_html_e( 'Estimated Ovulation Date', 'med-calculators' ); ?></div>
                    </div>
                    <div class="mcm-result-top__right">
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Fertile Window', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-fertile-window">&mdash;</span>
                        </div>
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Peak Fertility', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-peak-fertility">&mdash;</span>
                        </div>
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Next Period', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-next-period">&mdash;</span>
                        </div>
                    </div>
                </div>

                <!-- Fertile Days Timeline -->
                <div class="mcm-fertile-days" id="mcm-fertile-days"></div>

                <!-- Status -->
                <div class="mcm-status" id="mcm-fertility-status"></div>

                <!-- Disclaimer -->
                <div class="mcm-disclaimer"><?php echo wp_kses_post( $disclaimer ); ?></div>
            </div>
        </div>

    </div>
</div>
