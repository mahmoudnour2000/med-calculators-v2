<?php
/**
 * Calorie Calculator Class
 *
 * Calculates daily calorie needs using the Mifflin-St Jeor Equation.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Calories
 *
 * Calculates daily calorie requirements based on personal metrics.
 */
class Med_Calc_Calories {

    /**
     * Activity level multipliers
     *
     * @var array
     */
    const ACTIVITY_MULTIPLIERS = array(
        'sedentary'  => 1.2,    // Little or no exercise
        'light'      => 1.375,  // Light exercise 1-3 days/week
        'moderate'   => 1.55,   // Moderate exercise 3-5 days/week
        'active'     => 1.725,  // Hard exercise 6-7 days/week
        'very_active' => 1.9,   // Very hard exercise & physical job
    );

    /**
     * Minimum valid age
     *
     * @var int
     */
    const MIN_AGE = 15;

    /**
     * Maximum valid age
     *
     * @var int
     */
    const MAX_AGE = 120;

    /**
     * Minimum valid weight in kg
     *
     * @var float
     */
    const MIN_WEIGHT = 30;

    /**
     * Maximum valid weight in kg
     *
     * @var float
     */
    const MAX_WEIGHT = 300;

    /**
     * Minimum valid height in cm
     *
     * @var int
     */
    const MIN_HEIGHT = 100;

    /**
     * Maximum valid height in cm
     *
     * @var int
     */
    const MAX_HEIGHT = 250;

    /**
     * Calculate daily calorie needs
     *
     * @param array $data Form data.
     * @return array|WP_Error Result data or error.
     */
    public function calculate( array $data ) {
        // Sanitize inputs
        $gender = isset( $data['gender'] ) ? sanitize_key( $data['gender'] ) : '';
        $age = isset( $data['age'] ) ? absint( $data['age'] ) : 0;
        $weight = isset( $data['weight'] ) ? floatval( $data['weight'] ) : 0;
        $height = isset( $data['height'] ) ? floatval( $data['height'] ) : 0;
        $activity = isset( $data['activity'] ) ? sanitize_key( $data['activity'] ) : '';
        $goal = isset( $data['goal'] ) ? sanitize_key( $data['goal'] ) : 'maintain';

        // Validate inputs
        $validation = $this->validate_inputs( $gender, $age, $weight, $height, $activity );

        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        // Calculate BMR using Mifflin-St Jeor Equation
        $bmr = $this->calculate_bmr( $gender, $age, $weight, $height );

        // Calculate TDEE (Total Daily Energy Expenditure)
        $activity_multiplier = self::ACTIVITY_MULTIPLIERS[ $activity ];
        $tdee = round( $bmr * $activity_multiplier );

        // Calculate calorie targets for different goals
        $weight_loss_moderate = round( $tdee - 500 );  // 0.5 kg/week loss
        $weight_loss_aggressive = round( $tdee - 750 ); // 0.75 kg/week loss
        $weight_gain_moderate = round( $tdee + 500 );   // 0.5 kg/week gain
        $weight_gain_lean = round( $tdee + 250 );       // Lean bulk

        // Calculate BMI
        $height_m = $height / 100;
        $bmi = round( $weight / ( $height_m * $height_m ), 1 );
        $bmi_category = $this->get_bmi_category( $bmi );

        // Calculate macronutrient recommendations
        $macros = $this->calculate_macros( $tdee );

        // Get activity level label
        $activity_labels = $this->get_activity_labels();
        $activity_label = $activity_labels[ $activity ];

        // Calculate goal-based calories
        $goal_calories = $this->get_goal_calories( $tdee, $goal );
        $goal_macros = $this->calculate_macros( $goal_calories );

        return array(
            'result'              => sprintf(
                /* translators: %s: daily calories */
                __( 'Your daily calorie needs: %s kcal', 'med-calculators' ),
                number_format_i18n( $tdee )
            ),
            'bmr'                 => round( $bmr ),
            'bmr_formatted'       => number_format_i18n( round( $bmr ) ),
            'tdee'                => $tdee,
            'tdee_formatted'      => number_format_i18n( $tdee ),
            'bmi'                 => $bmi,
            'bmi_category'        => $bmi_category,
            'activity_level'      => $activity_label,
            'goals'               => array(
                'maintain'        => array(
                    'calories' => $tdee,
                    'label'    => __( 'Maintain Weight', 'med-calculators' ),
                ),
                'lose_moderate'   => array(
                    'calories' => max( 1200, $weight_loss_moderate ),
                    'label'    => __( 'Lose 0.5 kg/week', 'med-calculators' ),
                ),
                'lose_aggressive' => array(
                    'calories' => max( 1200, $weight_loss_aggressive ),
                    'label'    => __( 'Lose 0.75 kg/week', 'med-calculators' ),
                ),
                'gain_lean'       => array(
                    'calories' => $weight_gain_lean,
                    'label'    => __( 'Lean Bulk (+250)', 'med-calculators' ),
                ),
                'gain_moderate'   => array(
                    'calories' => $weight_gain_moderate,
                    'label'    => __( 'Gain 0.5 kg/week', 'med-calculators' ),
                ),
            ),
            'macros'              => $macros,
            'goal'                => $goal,
            'goal_calories'       => $goal_calories,
            'goal_macros'         => $goal_macros,
        );
    }

    /**
     * Get goal-based calorie adjustment
     *
     * @param int    $tdee TDEE value.
     * @param string $goal Goal type.
     * @return int Adjusted calories.
     */
    private function get_goal_calories( $tdee, $goal ) {
        switch ( $goal ) {
            case 'lose':
                return max( 1200, round( $tdee - 500 ) );
            case 'lose_10':
                return max( 1200, round( $tdee * 0.9 ) );
            case 'gain':
                return round( $tdee + 500 );
            case 'maintain':
            default:
                return $tdee;
        }
    }

    /**
     * Validate all inputs
     *
     * @param string $gender   Gender.
     * @param int    $age      Age.
     * @param float  $weight   Weight in kg.
     * @param float  $height   Height in cm.
     * @param string $activity Activity level.
     * @return true|WP_Error True if valid, error otherwise.
     */
    private function validate_inputs( $gender, $age, $weight, $height, $activity ) {
        // Validate gender
        if ( ! in_array( $gender, array( 'male', 'female' ), true ) ) {
            return new WP_Error(
                'invalid_gender',
                __( 'Please select your gender.', 'med-calculators' )
            );
        }

        // Validate age
        if ( $age < self::MIN_AGE || $age > self::MAX_AGE ) {
            return new WP_Error(
                'invalid_age',
                sprintf(
                    /* translators: 1: minimum age, 2: maximum age */
                    __( 'Age must be between %1$d and %2$d years.', 'med-calculators' ),
                    self::MIN_AGE,
                    self::MAX_AGE
                )
            );
        }

        // Validate weight
        if ( $weight < self::MIN_WEIGHT || $weight > self::MAX_WEIGHT ) {
            return new WP_Error(
                'invalid_weight',
                sprintf(
                    /* translators: 1: minimum weight, 2: maximum weight */
                    __( 'Weight must be between %1$d and %2$d kg.', 'med-calculators' ),
                    self::MIN_WEIGHT,
                    self::MAX_WEIGHT
                )
            );
        }

        // Validate height
        if ( $height < self::MIN_HEIGHT || $height > self::MAX_HEIGHT ) {
            return new WP_Error(
                'invalid_height',
                sprintf(
                    /* translators: 1: minimum height, 2: maximum height */
                    __( 'Height must be between %1$d and %2$d cm.', 'med-calculators' ),
                    self::MIN_HEIGHT,
                    self::MAX_HEIGHT
                )
            );
        }

        // Validate activity level
        if ( ! array_key_exists( $activity, self::ACTIVITY_MULTIPLIERS ) ) {
            return new WP_Error(
                'invalid_activity',
                __( 'Please select your activity level.', 'med-calculators' )
            );
        }

        return true;
    }

    /**
     * Calculate Basal Metabolic Rate using Mifflin-St Jeor Equation
     *
     * Male: BMR = 10 × weight(kg) + 6.25 × height(cm) − 5 × age(y) + 5
     * Female: BMR = 10 × weight(kg) + 6.25 × height(cm) − 5 × age(y) − 161
     *
     * @param string $gender Gender.
     * @param int    $age    Age in years.
     * @param float  $weight Weight in kg.
     * @param float  $height Height in cm.
     * @return float BMR value.
     */
    private function calculate_bmr( $gender, $age, $weight, $height ) {
        $base = ( 10 * $weight ) + ( 6.25 * $height ) - ( 5 * $age );

        if ( 'male' === $gender ) {
            return $base + 5;
        } else {
            return $base - 161;
        }
    }

    /**
     * Get BMI category based on WHO classification
     *
     * @param float $bmi BMI value.
     * @return array Category info.
     */
    private function get_bmi_category( $bmi ) {
        if ( $bmi < 18.5 ) {
            return array(
                'category' => 'underweight',
                'label'    => __( 'Underweight', 'med-calculators' ),
                'color'    => '#3498db',
            );
        } elseif ( $bmi < 25 ) {
            return array(
                'category' => 'normal',
                'label'    => __( 'Normal Weight', 'med-calculators' ),
                'color'    => '#27ae60',
            );
        } elseif ( $bmi < 30 ) {
            return array(
                'category' => 'overweight',
                'label'    => __( 'Overweight', 'med-calculators' ),
                'color'    => '#f39c12',
            );
        } else {
            return array(
                'category' => 'obese',
                'label'    => __( 'Obese', 'med-calculators' ),
                'color'    => '#e74c3c',
            );
        }
    }

    /**
     * Calculate macronutrient recommendations
     *
     * @param int $calories Daily calories.
     * @return array Macros breakdown.
     */
    private function calculate_macros( $calories ) {
        // Balanced macro split: 30% protein, 40% carbs, 30% fat
        $protein_percent = 0.30;
        $carbs_percent = 0.40;
        $fat_percent = 0.30;

        // Calories per gram
        $protein_cal_per_g = 4;
        $carbs_cal_per_g = 4;
        $fat_cal_per_g = 9;

        return array(
            'protein' => array(
                'grams'    => round( ( $calories * $protein_percent ) / $protein_cal_per_g ),
                'calories' => round( $calories * $protein_percent ),
                'percent'  => 30,
            ),
            'carbs'   => array(
                'grams'    => round( ( $calories * $carbs_percent ) / $carbs_cal_per_g ),
                'calories' => round( $calories * $carbs_percent ),
                'percent'  => 40,
            ),
            'fat'     => array(
                'grams'    => round( ( $calories * $fat_percent ) / $fat_cal_per_g ),
                'calories' => round( $calories * $fat_percent ),
                'percent'  => 30,
            ),
        );
    }

    /**
     * Get activity level labels
     *
     * @return array Activity labels.
     */
    private function get_activity_labels() {
        return array(
            'sedentary'   => __( 'Sedentary (little or no exercise)', 'med-calculators' ),
            'light'       => __( 'Lightly Active (light exercise 1-3 days/week)', 'med-calculators' ),
            'moderate'    => __( 'Moderately Active (moderate exercise 3-5 days/week)', 'med-calculators' ),
            'active'      => __( 'Very Active (hard exercise 6-7 days/week)', 'med-calculators' ),
            'very_active' => __( 'Extra Active (very hard exercise & physical job)', 'med-calculators' ),
        );
    }
}

