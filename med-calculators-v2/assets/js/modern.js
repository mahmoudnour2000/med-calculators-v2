/**
 * Modern Calculator JavaScript — All Calculators
 * Calories / Ovulation / Pregnancy
 * Fully translatable via medCalcConfig.i18n
 * @package MedCalculators
 */

(function () {
    'use strict';

    /* =====================
       i18n helper — falls back to English
       ===================== */
    var i18n = (typeof medCalcConfig !== 'undefined' && medCalcConfig.i18n) ? medCalcConfig.i18n : {};

    function t(key, fallback) {
        return i18n[key] || fallback;
    }

    /* =====================
       Activity mapping (calories)
       ===================== */
    var ACTIVITY_MAP = [
        {
            key: 'sedentary',
            label: t('activity_low', 'Low'),
            desc: t('activity_low_desc', 'Little or no exercise. Desk job with minimal movement.')
        },
        {
            key: 'moderate',
            label: t('activity_middle', 'Middle'),
            desc: t('activity_middle_desc', 'Activity that burns an additional 400-650 calories for females or 500-800 calories for males.')
        },
        {
            key: 'active',
            label: t('activity_high', 'High'),
            desc: t('activity_high_desc', 'Intense exercise 6-7 days/week. Very physically demanding lifestyle.')
        },
        {
            key: 'very_active',
            label: t('activity_very_high', 'Very High'),
            desc: t('activity_very_high_desc', 'Extremely active. Athlete-level training twice per day.')
        }
    ];

    /* =====================
       Boot
       ===================== */
    function boot() {
        document.querySelectorAll('.mcm').forEach(function (root) {
            var type = root.dataset.calculator;
            var form = root.querySelector('.mcm-form');
            if (!form) return;

            // Shared
            setupClear(root, form);
            setupSubmit(form, root, type);

            // Type-specific
            if (type === 'calories') {
                setupGender(root);
                setupActivitySlider(root);
                setupGoals(root);
                setupMealTabs(root);
                setupProteinSlider(root);
            }
            if (type === 'ovulation') {
                setupCycleSlider(root);
            }
        });
    }

    /* =====================
       Gender toggle (calories)
       ===================== */
    function setupGender(root) {
        var btns = root.querySelectorAll('.mcm-gender__btn');
        var hidden = root.querySelector('input[name="gender"]');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                btns.forEach(function (b) { b.classList.remove('mcm-gender__btn--active'); });
                btn.classList.add('mcm-gender__btn--active');
                if (hidden) hidden.value = btn.dataset.gender;
            });
        });
    }

    /* =====================
       Activity Slider (calories)
       ===================== */
    function setupActivitySlider(root) {
        var slider = root.querySelector('#mcm-activity-slider');
        var labels = root.querySelectorAll('.mcm-slider-label');
        var desc = root.querySelector('.mcm-block__desc');

        if (!slider) return;

        function sync(idx) {
            labels.forEach(function (l, i) { l.classList.toggle('mcm-slider-label--active', i === idx); });
            if (desc) {
                desc.innerHTML = '<strong class="mcm-activity-label">' + ACTIVITY_MAP[idx].label + ':</strong> ' + ACTIVITY_MAP[idx].desc;
            }
        }

        slider.addEventListener('input', function () { sync(parseInt(slider.value)); });
        labels.forEach(function (label, i) {
            label.addEventListener('click', function () { slider.value = i; sync(i); });
        });
    }

    /* =====================
       Cycle Slider (ovulation)
       ===================== */
    function setupCycleSlider(root) {
        var slider = root.querySelector('#mcm-cycle-slider');
        var display = root.querySelector('#mcm-cycle-value');
        var hidden = root.querySelector('#mcm-cycle-hidden');
        if (!slider) return;

        slider.addEventListener('input', function () {
            if (display) display.textContent = slider.value;
            if (hidden) hidden.value = slider.value;
        });
    }

    /* =====================
       Goals (calories)
       ===================== */
    function setupGoals(root) {
        var btns = root.querySelectorAll('.mcm-goal');
        var hidden = root.querySelector('input[name="goal"]');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                btns.forEach(function (b) { b.classList.remove('mcm-goal--active'); });
                btn.classList.add('mcm-goal--active');
                if (hidden) hidden.value = btn.dataset.goal;
            });
        });
    }

    /* =====================
       Clear
       ===================== */
    function setupClear(root, form) {
        var btn = root.querySelector('.mcm-clear-btn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            form.reset();

            // Gender reset
            var gBtns = root.querySelectorAll('.mcm-gender__btn');
            gBtns.forEach(function (b, i) { b.classList.toggle('mcm-gender__btn--active', i === 0); });
            var gH = root.querySelector('input[name="gender"]');
            if (gH) gH.value = 'male';

            // Activity reset
            var actSlider = root.querySelector('#mcm-activity-slider');
            if (actSlider) { actSlider.value = 1; actSlider.dispatchEvent(new Event('input')); }

            // Goal reset
            var goalBtns = root.querySelectorAll('.mcm-goal');
            goalBtns.forEach(function (b) { b.classList.toggle('mcm-goal--active', b.dataset.goal === 'maintain'); });
            var goalH = root.querySelector('input[name="goal"]');
            if (goalH) goalH.value = 'maintain';

            // Cycle reset
            var cycleSlider = root.querySelector('#mcm-cycle-slider');
            if (cycleSlider) { cycleSlider.value = 28; cycleSlider.dispatchEvent(new Event('input')); }

            // Hide result
            var ph = root.querySelector('#mcm-placeholder');
            var res = root.querySelector('#mcm-result');
            if (ph) ph.style.display = '';
            if (res) res.style.display = 'none';
        });
    }

    /* =====================
       Submit / AJAX
       ===================== */
    function setupSubmit(form, root, calcType) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!form.checkValidity()) { form.reportValidity(); return; }

            var fd = new FormData(form);

            // Activity key for calories
            if (calcType === 'calories') {
                var actSlider = form.querySelector('#mcm-activity-slider');
                if (actSlider) fd.set('activity', ACTIVITY_MAP[parseInt(actSlider.value)].key);
            }

            toggleLoader(root, true);

            var body = new URLSearchParams({
                action: 'med_calc',
                nonce: (typeof medCalcConfig !== 'undefined') ? medCalcConfig.nonce : '',
                type: calcType
            });
            for (var pair of fd.entries()) body.set(pair[0], pair[1]);

            var ajaxUrl = (typeof medCalcConfig !== 'undefined') ? medCalcConfig.ajaxUrl : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                toggleLoader(root, false);
                if (res.success && res.data && res.data.result) {
                    showResults(root, res.data.result, calcType);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : t('calculation_error', 'Calculation error');
                    alert(msg);
                }
            })
            .catch(function () {
                toggleLoader(root, false);
                alert(t('network_error', 'Network error. Please try again.'));
            });
        });
    }

    function toggleLoader(root, show) {
        var loader = root.querySelector('.mcm-loader');
        var btn = root.querySelector('.mcm-calc-btn');
        if (loader) loader.style.display = show ? 'flex' : 'none';
        if (btn) btn.disabled = show;
    }

    /* =====================
       Show Results (router)
       ===================== */
    function showResults(root, data, type) {
        var ph = root.querySelector('#mcm-placeholder');
        var res = root.querySelector('#mcm-result');
        if (!res) return;
        if (ph) ph.style.display = 'none';
        res.style.display = 'flex';

        if (type === 'calories')  showCaloriesResult(root, data);
        if (type === 'ovulation') showOvulationResult(root, data);
        if (type === 'pregnancy') showPregnancyResult(root, data);

        // Scroll on mobile
        if (window.innerWidth <= 700) {
            res.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /* =====================
       CALORIES Result
       ===================== */
    function showCaloriesResult(root, data) {
        var goalCal = data.goal_calories || data.tdee || 0;
        animateNumber(root.querySelector('#mcm-kcal-number'), goalCal);

        var macros = data.goal_macros || data.macros || null;
        if (macros) {
            setMacro(root, '#mcm-macro-carbs', macros.carbs);
            setMacro(root, '#mcm-macro-protein', macros.protein);
            setMacro(root, '#mcm-macro-fat', macros.fat);
        }

        var res = root.querySelector('#mcm-result');
        if (res) {
            res.dataset.calories = goalCal;
            res.dataset.macros = JSON.stringify(macros);
        }

        // Reset tabs & protein
        root.querySelectorAll('.mcm-tab').forEach(function (t) { t.classList.toggle('mcm-tab--active', t.dataset.meals === '1'); });
        var ps = root.querySelector('#mcm-protein-slider');
        if (ps) { ps.value = 1; syncProteinLabels(root, 1); }
    }

    /* =====================
       OVULATION Result
       ===================== */
    function showOvulationResult(root, data) {
        setText(root, '#mcm-ovulation-date', data.ovulation_date || '\u2014');
        setText(root, '#mcm-fertile-window', data.fertile_window || '\u2014');
        setText(root, '#mcm-peak-fertility', data.peak_fertility || '\u2014');
        setText(root, '#mcm-next-period', data.next_period || '\u2014');

        // Fertile days
        var container = root.querySelector('#mcm-fertile-days');
        if (container && data.fertile_days) {
            container.innerHTML = '';
            data.fertile_days.forEach(function (day) {
                var el = document.createElement('div');
                var cls = day.is_ovulation ? 'mcm-fertile-day--ovulation' : ('mcm-fertile-day--' + day.level);
                el.className = 'mcm-fertile-day ' + cls;
                el.textContent = day.date;
                container.appendChild(el);
            });
        }

        // Status
        var statusEl = root.querySelector('#mcm-fertility-status');
        if (statusEl && data.fertility_status) {
            statusEl.textContent = data.fertility_status.message || '';
        }
    }

    /* =====================
       PREGNANCY Result
       ===================== */
    function showPregnancyResult(root, data) {
        setText(root, '#mcm-due-date', data.due_date || '\u2014');

        /* translators: Week %d */
        var weekLabel = t('week_label', 'Week');
        setText(root, '#mcm-current-week', weekLabel + ' ' + (data.current_week || '\u2014'));

        setText(root, '#mcm-trimester', ordinalTrimester(data.trimester));

        /* translators: %d days */
        var daysLabel = t('days_label', 'days');
        setText(root, '#mcm-days-remaining', (data.days_remaining || 0) + ' ' + daysLabel);

        setText(root, '#mcm-conception-date', data.conception_date || '\u2014');

        /* translators: %d weeks */
        var weeksLabel = t('weeks_label', 'weeks');
        setText(root, '#mcm-weeks-remaining', (data.weeks_remaining || 0) + ' ' + weeksLabel);

        // Progress bar
        var fill = root.querySelector('#mcm-progress-fill');
        if (fill && data.current_week) {
            var pct = Math.min(100, Math.round((data.current_week / 40) * 100));
            fill.style.width = pct + '%';
        }
    }

    function ordinalTrimester(n) {
        if (n === 1) return t('trimester_1st', '1st Trimester');
        if (n === 2) return t('trimester_2nd', '2nd Trimester');
        if (n === 3) return t('trimester_3rd', '3rd Trimester');
        return '\u2014';
    }

    /* =====================
       Helpers
       ===================== */
    function setText(root, sel, text) {
        var el = root.querySelector(sel);
        if (el) el.textContent = text;
    }

    function setMacro(root, sel, obj) {
        var el = root.querySelector(sel);
        if (el && obj) el.textContent = obj.grams + t('g_unit', 'g') + '/' + obj.percent + '%';
    }

    function animateNumber(el, target) {
        if (!el) return;
        var start = parseInt(el.textContent) || 0;
        var t0 = performance.now();
        var dur = 700;

        function tick(now) {
            var p = Math.min((now - t0) / dur, 1);
            var ease = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(start + (target - start) * ease);
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    /* =====================
       Meal Tabs (calories)
       ===================== */
    function setupMealTabs(root) {
        root.querySelectorAll('.mcm-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                root.querySelectorAll('.mcm-tab').forEach(function (t) { t.classList.remove('mcm-tab--active'); });
                tab.classList.add('mcm-tab--active');

                var meals = parseInt(tab.dataset.meals);
                var res = root.querySelector('#mcm-result');
                if (!res || !res.dataset.macros) return;

                try {
                    var macros = JSON.parse(res.dataset.macros);
                    if (meals === 1) {
                        setMacro(root, '#mcm-macro-carbs', macros.carbs);
                        setMacro(root, '#mcm-macro-protein', macros.protein);
                        setMacro(root, '#mcm-macro-fat', macros.fat);
                    } else {
                        setMacroMeal(root, '#mcm-macro-carbs', macros.carbs, meals);
                        setMacroMeal(root, '#mcm-macro-protein', macros.protein, meals);
                        setMacroMeal(root, '#mcm-macro-fat', macros.fat, meals);
                    }
                } catch (e) {}
            });
        });
    }

    function setMacroMeal(root, sel, obj, meals) {
        var el = root.querySelector(sel);
        if (el && obj) el.textContent = Math.round(obj.grams / meals) + t('g_per_meal', 'g/meal');
    }

    /* =====================
       Protein Slider (calories)
       ===================== */
    function setupProteinSlider(root) {
        var slider = root.querySelector('#mcm-protein-slider');
        if (!slider) return;

        slider.addEventListener('input', function () {
            var idx = parseInt(slider.value);
            syncProteinLabels(root, idx);
            recalcMacros(root, idx);
        });

        root.querySelectorAll('.mcm-protein-label').forEach(function (label) {
            label.addEventListener('click', function () {
                var idx = parseInt(label.dataset.level);
                slider.value = idx;
                syncProteinLabels(root, idx);
                recalcMacros(root, idx);
            });
        });
    }

    function syncProteinLabels(root, idx) {
        root.querySelectorAll('.mcm-protein-label').forEach(function (l, i) {
            l.classList.toggle('mcm-protein-label--active', i === idx);
        });
    }

    function recalcMacros(root, levelIdx) {
        var res = root.querySelector('#mcm-result');
        if (!res) return;
        var cal = parseInt(res.dataset.calories);
        if (!cal) return;

        var splits = [
            { p: 0.20, c: 0.50, f: 0.30 },
            { p: 0.30, c: 0.40, f: 0.30 },
            { p: 0.40, c: 0.35, f: 0.25 }
        ];
        var s = splits[levelIdx] || splits[1];

        var macros = {
            carbs:   { grams: Math.round((cal * s.c) / 4), percent: Math.round(s.c * 100) },
            protein: { grams: Math.round((cal * s.p) / 4), percent: Math.round(s.p * 100) },
            fat:     { grams: Math.round((cal * s.f) / 9), percent: Math.round(s.f * 100) }
        };

        setMacro(root, '#mcm-macro-carbs', macros.carbs);
        setMacro(root, '#mcm-macro-protein', macros.protein);
        setMacro(root, '#mcm-macro-fat', macros.fat);
        res.dataset.macros = JSON.stringify(macros);

        root.querySelectorAll('.mcm-tab').forEach(function (t) { t.classList.toggle('mcm-tab--active', t.dataset.meals === '1'); });
    }

    /* =====================
       Init
       ===================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})();
