<?php
/**
 * Modern Calories Calculator Template
 * Exact match to reference design — fully translatable
 *
 * @package MedCalculators
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'med_calc_settings', array() );
$disclaimer = isset( $settings['disclaimer_text'] ) && ! empty( $settings['disclaimer_text'] )
    ? $settings['disclaimer_text']
    : __( 'This calculator is for informational purposes only and should not replace professional medical advice.', 'med-calculators' );

// Cache-busting attributes for Elementor (forces cache refresh on language/version change)
$current_locale = get_locale();
?>

<div class="mcm" data-calculator="calories" data-locale="<?php echo esc_attr( $current_locale ); ?>" data-version="<?php echo esc_attr( MED_CALC_VERSION ); ?>">
    <div class="mcm-wrapper">

        <!-- ====== LEFT: Orange Form ====== -->
        <div class="mcm-form-panel">
            <form class="mcm-form" data-calculator-type="calories">

                <!-- Body Parameters -->
                <div class="mcm-block">
                    <h3 class="mcm-block__title"><?php esc_html_e( 'Body Parameters', 'med-calculators' ); ?></h3>

                    <div class="mcm-gender">
                        <button type="button" class="mcm-gender__btn mcm-gender__btn--active" data-gender="male"><?php esc_html_e( 'MALE', 'med-calculators' ); ?></button>
                        <button type="button" class="mcm-gender__btn" data-gender="female"><?php esc_html_e( 'FEMALE', 'med-calculators' ); ?></button>
                        <input type="hidden" name="gender" value="male">
                    </div>

                    <div class="mcm-inputs">
                        <div class="mcm-input-box">
                            <input type="number" name="age" class="mcm-input-box__field" value="24" min="15" max="80" required>
                            <span class="mcm-input-box__unit"><?php esc_html_e( 'AGE', 'med-calculators' ); ?></span>
                        </div>
                        <div class="mcm-input-box">
                            <input type="number" name="weight" class="mcm-input-box__field" value="72" min="30" max="300" step="0.1" required>
                            <span class="mcm-input-box__unit"><?php esc_html_e( 'KG', 'med-calculators' ); ?></span>
                        </div>
                        <div class="mcm-input-box">
                            <input type="number" name="height" class="mcm-input-box__field" value="182" min="100" max="250" required>
                            <span class="mcm-input-box__unit"><?php esc_html_e( 'CM', 'med-calculators' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Activity Level -->
                <div class="mcm-block">
                    <h3 class="mcm-block__title"><?php esc_html_e( 'Activity Level', 'med-calculators' ); ?></h3>
                    <p class="mcm-block__desc">
                        <strong class="mcm-activity-label"><?php esc_html_e( 'Middle', 'med-calculators' ); ?>:</strong>
                        <?php esc_html_e( 'Activity that burns an additional 400-650 calories for females or 500-800 calories for males.', 'med-calculators' ); ?>
                    </p>

                    <div class="mcm-slider-wrap">
                        <input type="range" min="0" max="3" value="1" step="1" class="mcm-slider" id="mcm-activity-slider">
                    </div>
                    <div class="mcm-slider-labels">
                        <span class="mcm-slider-label" data-idx="0"><?php esc_html_e( 'LOW', 'med-calculators' ); ?></span>
                        <span class="mcm-slider-label mcm-slider-label--active" data-idx="1"><?php esc_html_e( 'MIDDLE', 'med-calculators' ); ?></span>
                        <span class="mcm-slider-label" data-idx="2"><?php esc_html_e( 'HIGH', 'med-calculators' ); ?></span>
                        <span class="mcm-slider-label" data-idx="3"><?php esc_html_e( 'VERY HIGH', 'med-calculators' ); ?></span>
                    </div>
                </div>

                <!-- Goals -->
                <div class="mcm-block">
                    <h3 class="mcm-block__title"><?php esc_html_e( 'Goals', 'med-calculators' ); ?></h3>
                    <div class="mcm-goals">
                        <button type="button" class="mcm-goal" data-goal="lose"><?php esc_html_e( 'LOSE', 'med-calculators' ); ?></button>
                        <button type="button" class="mcm-goal" data-goal="lose_10"><?php esc_html_e( 'LOSE 10%', 'med-calculators' ); ?></button>
                        <button type="button" class="mcm-goal mcm-goal--active" data-goal="maintain"><?php esc_html_e( 'MAINTAIN', 'med-calculators' ); ?></button>
                        <button type="button" class="mcm-goal" data-goal="gain"><?php esc_html_e( 'GAIN', 'med-calculators' ); ?></button>
                    </div>
                    <input type="hidden" name="goal" value="maintain">
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

        <!-- ====== RIGHT: White Results ====== -->
        <div class="mcm-result-panel">

            <!-- Placeholder (before calculation) -->
            <div class="mcm-placeholder" id="mcm-placeholder">
                <h2 class="mcm-result__heading"><?php esc_html_e( 'Your Result', 'med-calculators' ); ?></h2>
                <div class="mcm-placeholder__inner">
                    <p><?php esc_html_e( 'Fill in the form and press Calculate to see your results.', 'med-calculators' ); ?></p>
                </div>
            </div>

            <!-- Result (after calculation) -->
            <div class="mcm-result" id="mcm-result" style="display:none;">
                <h2 class="mcm-result__heading"><?php esc_html_e( 'Your Result', 'med-calculators' ); ?></h2>

                <!-- Top row: kcal left + macros right -->
                <div class="mcm-result-top">
                    <div class="mcm-result-top__left">
                        <div class="mcm-kcal">
                            <span class="mcm-kcal__number" id="mcm-kcal-number">1890</span>
                            <span class="mcm-kcal__unit"><?php esc_html_e( 'kcal', 'med-calculators' ); ?></span>
                        </div>
                        <div class="mcm-kcal__sub"><?php esc_html_e( 'Suggested amount of calories per day.', 'med-calculators' ); ?></div>
                    </div>
                    <div class="mcm-result-top__right">
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Carbohydrate', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-macro-carbs">216g/45.8%</span>
                        </div>
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Protein', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-macro-protein">138g/29.22%</span>
                        </div>
                        <div class="mcm-macro-row">
                            <span class="mcm-macro-row__label"><?php esc_html_e( 'Fat', 'med-calculators' ); ?></span>
                            <span class="mcm-macro-row__value" id="mcm-macro-fat">53g/25.0%</span>
                        </div>
                    </div>
                </div>

                <!-- Meal distribution tabs -->
                <div class="mcm-tabs">
                    <button class="mcm-tab mcm-tab--active" data-meals="1"><?php esc_html_e( 'PER DAY', 'med-calculators' ); ?></button>
                    <button class="mcm-tab" data-meals="3"><?php
                        /* translators: %d: number of meals */
                        printf( esc_html__( '%d MEALS', 'med-calculators' ), 3 );
                    ?></button>
                    <button class="mcm-tab" data-meals="4"><?php printf( esc_html__( '%d MEALS', 'med-calculators' ), 4 ); ?></button>
                    <button class="mcm-tab" data-meals="5"><?php printf( esc_html__( '%d MEALS', 'med-calculators' ), 5 ); ?></button>
                </div>

                <!-- Adjust Protein -->
                <div class="mcm-adjust">
                    <h4 class="mcm-adjust__title"><?php esc_html_e( 'Adjust Protein', 'med-calculators' ); ?></h4>
                    <p class="mcm-adjust__desc"><?php esc_html_e( 'We recommend to start with normal level. If you do a lot of lifting, try "high".', 'med-calculators' ); ?></p>
                    <div class="mcm-protein-slider-wrap">
                        <input type="range" min="0" max="2" value="1" step="1" class="mcm-protein-slider" id="mcm-protein-slider">
                    </div>
                    <div class="mcm-protein-labels">
                        <span class="mcm-protein-label" data-level="0"><?php esc_html_e( 'LOW', 'med-calculators' ); ?></span>
                        <span class="mcm-protein-label mcm-protein-label--active" data-level="1"><?php esc_html_e( 'NORMAL', 'med-calculators' ); ?></span>
                        <span class="mcm-protein-label" data-level="2"><?php esc_html_e( 'HIGH', 'med-calculators' ); ?></span>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="mcm-disclaimer"><?php echo wp_kses_post( $disclaimer ); ?></div>
            </div>
        </div>

    </div>
</div>
