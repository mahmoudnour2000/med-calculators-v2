<?php
/**
 * Modern Pregnancy Due Date Calculator Template
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

<div class="mcm" data-calculator="pregnancy" data-locale="<?php echo esc_attr( $current_locale ); ?>" data-version="<?php echo esc_attr( MED_CALC_VERSION ); ?>">
    <div class="mcm-wrapper">

        <!-- ====== LEFT: Form Panel ====== -->
        <div class="mcm-form-panel">
            <form class="mcm-form" data-calculator-type="pregnancy">

                <div class="mcm-block">
                    <h3 class="mcm-block__title"><?php esc_html_e( 'Pregnancy Information', 'med-calculators' ); ?></h3>

                    <!-- Last Menstrual Period -->
                    <div class="mcm-field">
                        <label class="mcm-field__label"><?php esc_html_e( 'First Day of Last Menstrual Period', 'med-calculators' ); ?></label>
                        <p class="mcm-block__desc"><?php esc_html_e( 'Enter the first day of your last menstrual period to calculate your expected due date using Naegele\'s rule.', 'med-calculators' ); ?></p>
                        <div class="mcm-input-box mcm-input-box--wide">
                            <input type="date" name="lmp" class="mcm-input-box__field mcm-input-box__field--date" required>
                        </div>
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
                    <p><?php esc_html_e( 'Enter the date of your last menstrual period and press Calculate.', 'med-calculators' ); ?></p>
                </div>
            </div>

            <div class="mcm-result" id="mcm-result" style="display:none;">
                <h2 class="mcm-result__heading"><?php esc_html_e( 'Your Result', 'med-calculators' ); ?></h2>

                <div class="mcm-result-top">
                    <div class="mcm-result-top__left">
                        <div class="mcm-kcal">
                            <span class="mcm-kcal__number mcm-kcal__number--date" id="mcm-due-date">&mdash;</span>
                        </div>
                        <div class="mcm-kcal__sub"><?php esc_html_e( 'Expected Due Date', 'med-calculators' ); ?></div>
                    </div>
                    <div class="mcm-result-top__right">
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Current Week', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-current-week">&mdash;</span>
                        </div>
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Trimester', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-trimester">&mdash;</span>
                        </div>
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Days Remaining', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-days-remaining">&mdash;</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mcm-progress-wrap">
                    <div class="mcm-progress-bar">
                        <div class="mcm-progress-bar__fill" id="mcm-progress-fill" style="width:0%"></div>
                    </div>
                    <div class="mcm-progress-labels">
                        <?php
                        /* translators: %d: week number */
                        ?>
                        <span><?php printf( esc_html__( 'Week %d', 'med-calculators' ), 1 ); ?></span>
                        <span><?php printf( esc_html__( 'Week %d', 'med-calculators' ), 13 ); ?></span>
                        <span><?php printf( esc_html__( 'Week %d', 'med-calculators' ), 27 ); ?></span>
                        <span><?php printf( esc_html__( 'Week %d', 'med-calculators' ), 40 ); ?></span>
                    </div>
                </div>

                <!-- Conception Date -->
                <div class="mcm-extra-info">
                    <div class="mcm-macro-row">
                        <span class="mcm-macro-row__label"><?php esc_html_e( 'Estimated Conception Date', 'med-calculators' ); ?></span>
                        <span class="mcm-macro-row__value" id="mcm-conception-date">&mdash;</span>
                    </div>
                    <div class="mcm-macro-row">
                        <span class="mcm-macro-row__label"><?php esc_html_e( 'Weeks Remaining', 'med-calculators' ); ?></span>
                        <span class="mcm-macro-row__value" id="mcm-weeks-remaining">&mdash;</span>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="mcm-disclaimer"><?php echo wp_kses_post( $disclaimer ); ?></div>
            </div>
        </div>

    </div>
</div>
