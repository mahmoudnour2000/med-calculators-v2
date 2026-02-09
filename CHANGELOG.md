# Changelog | سجل التحديثات

All notable changes to Med Calculators will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.8] - 2026-02-09

### ✨ Added | إضافات

<div dir="rtl">

- ✅ نظام ترجمة PHP كامل ومستقل عن ملفات `.mo`
- ✅ إضافة تسمية "السن" في حاسبة السعرات الحرارية
- ✅ إضافة ترجمة الصيغ المتعددة (plural forms) في حاسبة التبويض
- ✅ نظام متقدم لإخفاء إشعارات Elementor
- ✅ تحسين توافقية الكاش مع Elementor

</div>

- ✅ Complete PHP-based translation system independent of `.mo` files
- ✅ Added "Age" label in Calorie calculator UI
- ✅ Added plural form translations for Ovulation calculator
- ✅ Advanced Elementor notice suppression system
- ✅ Improved Elementor cache compatibility

### 🔧 Fixed | إصلاحات

<div dir="rtl">

- 🐛 إصلاح إشعارات `WP_Scripts::add` و `WP_Styles::add` في محرر Elementor
- 🐛 إصلاح ترجمة النصوص في السياقات المختلفة
- 🐛 إصلاح مشاكل الكاش عند تغيير اللغة
- 🐛 إصلاح عرض الترجمة العربية في جميع القوالب

</div>

- 🐛 Fixed `WP_Scripts::add` and `WP_Styles::add` notices in Elementor editor
- 🐛 Fixed text translation in different contexts
- 🐛 Fixed cache issues when changing language
- 🐛 Fixed Arabic translation display in all templates

### ⚡ Improved | تحسينات

<div dir="rtl">

- ⚡ تحسين أداء تحميل الترجمات
- ⚡ تحسين التوافقية مع WordPress 6.9.1+
- ⚡ تقليل استعلامات قاعدة البيانات
- ⚡ تحسين سرعة تحميل الإضافة

</div>

- ⚡ Improved translation loading performance
- ⚡ Enhanced compatibility with WordPress 6.9.1+
- ⚡ Reduced database queries
- ⚡ Improved plugin loading speed

---

## [1.0.7] - 2026-02-07

### ✨ Added | إضافات

- ✅ PHP translation fallback system using `gettext` filters
- ✅ Enhanced error suppression for development environments
- ✅ Multi-layer Elementor compatibility fixes

### 🔧 Fixed | إصلاحات

- 🐛 Fixed `.mo` file loading issues
- 🐛 Fixed translation not showing on first load
- 🐛 Fixed Elementor editor warnings

### 📚 Documentation | توثيق

- 📖 Added comprehensive translation documentation
- 📖 Updated README with troubleshooting section

---

## [1.0.6] - 2026-02-05

### ✨ Added | إضافات

<div dir="rtl">

- 🎨 تصميم عصري جديد بالكامل مع خلفيات متدرجة
- 🌍 ترجمة عربية كاملة مع دعم RTL
- 📧 نظام جمع البريد الإلكتروني (اختياري)
- 📊 لوحة تحكم للإدارة مع الإحصائيات
- 💾 تسجيل الحسابات في قاعدة البيانات
- 📑 تصدير البيانات إلى CSV
- 🔒 خيارات الامتثال لـ GDPR

</div>

- 🎨 Brand new modern design with gradient backgrounds
- 🌍 Complete Arabic translation with RTL support
- 📧 Email collection system (optional)
- 📊 Admin dashboard with statistics
- 💾 Calculation logging to database
- 📑 CSV export functionality
- 🔒 GDPR compliance options

### ⚙️ Technical | تقني

- ⚙️ Object-oriented architecture
- ⚙️ PSR-4 autoloading
- ⚙️ WordPress coding standards
- ⚙️ Secure data handling
- ⚙️ Nonce verification
- ⚙️ SQL injection prevention

---

## [1.0.5] - 2026-01-20

### ✨ Added | إضافات

- 🔥 Calorie & Macros Calculator
  - BMR calculation (Mifflin-St Jeor equation)
  - TDEE with activity level
  - Macros distribution (protein, carbs, fats)
  - Weight goal options (lose/gain/maintain)
  - Customizable protein intake

### 🎨 Design | تصميم

- Modern gradient UI
- Smooth animations
- Interactive sliders
- Visual result cards

---

## [1.0.4] - 2026-01-15

### ✨ Added | إضافات

- 🌸 Ovulation & Fertility Calculator
  - Ovulation day prediction
  - 6-day fertile window
  - Peak fertility identification
  - Cycle length customization
  - Interactive calendar view

### 🔧 Improved | تحسينات

- Better date handling
- Improved calculation accuracy
- Enhanced mobile responsiveness

---

## [1.0.3] - 2026-01-10

### 🔧 Fixed | إصلاحات

- 🐛 Fixed date calculation edge cases
- 🐛 Fixed timezone handling issues
- 🐛 Improved input validation

### ⚡ Performance | الأداء

- ⚡ Optimized JavaScript execution
- ⚡ Reduced CSS file size
- ⚡ Faster AJAX responses

---

## [1.0.2] - 2026-01-05

### ✨ Added | إضافات

- 📱 Responsive design improvements
- 🎨 Better mobile UI/UX
- ⌨️ Keyboard navigation support

### 🔧 Fixed | إصلاحات

- 🐛 Fixed layout issues on small screens
- 🐛 Fixed button alignment
- 🐛 Improved form validation messages

---

## [1.0.1] - 2025-12-28

### ✨ Added | إضافات

- 🤰 Pregnancy Due Date Calculator (Initial Release)
  - LMP-based calculation
  - Conception date option
  - Current week display
  - Trimester identification

### 🎨 Design | تصميم

- Clean, modern interface
- Gradient color scheme
- Smooth transitions
- Clear result display

---

## [1.0.0] - 2025-12-20

### 🎉 Initial Release | الإصدار الأول

<div dir="rtl">

- 🎉 أول إصدار من إضافة Med Calculators
- 🏗️ بنية معمارية قوية وقابلة للتوسع
- 📦 نظام shortcode سهل الاستخدام
- ⚙️ صفحة إعدادات في لوحة التحكم
- 🔐 أمان وحماية قوية للبيانات

</div>

- 🎉 First release of Med Calculators plugin
- 🏗️ Solid, scalable architecture
- 📦 Easy-to-use shortcode system
- ⚙️ Admin settings page
- 🔐 Strong security and data protection

---

## 📋 Version History Summary

| Version | Date | Type | Highlights |
|---------|------|------|-----------|
| **1.0.8** | 2026-02-09 | 🔧 Fix | Complete translation system, Elementor fixes |
| **1.0.7** | 2026-02-07 | 🔧 Fix | PHP translation fallback, compatibility |
| **1.0.6** | 2026-02-05 | ✨ Major | Modern design, Arabic RTL, Admin dashboard |
| **1.0.5** | 2026-01-20 | ✨ Feature | Calorie Calculator added |
| **1.0.4** | 2026-01-15 | ✨ Feature | Ovulation Calculator added |
| **1.0.3** | 2026-01-10 | 🔧 Fix | Bug fixes and performance |
| **1.0.2** | 2026-01-05 | 🎨 Design | Responsive improvements |
| **1.0.1** | 2025-12-28 | ✨ Feature | Pregnancy Calculator |
| **1.0.0** | 2025-12-20 | 🎉 Initial | First release |

---

## 🔮 Upcoming Features | المميزات القادمة

<div dir="rtl">

### نخطط لإضافة:

- 🩺 **حاسبة BMI (مؤشر كتلة الجسم)**
  - حساب BMI بدقة
  - تصنيف الوزن
  - توصيات صحية

- 💧 **حاسبة الماء اليومية**
  - حساب احتياج الماء
  - مع مراعاة النشاط
  - تذكيرات شرب الماء

- 🏃 **حاسبة معدل ضربات القلب المثالي**
  - أثناء التمرين
  - حسب العمر والهدف
  - مناطق معدل القلب

- 📊 **تقارير متقدمة**
  - رسوم بيانية
  - تتبع التقدم
  - مقارنات شهرية

</div>

### We're Planning to Add:

- 🩺 **BMI Calculator**
  - Accurate BMI calculation
  - Weight classification
  - Health recommendations

- 💧 **Daily Water Intake Calculator**
  - Water needs calculation
  - Activity-adjusted
  - Hydration reminders

- 🏃 **Target Heart Rate Calculator**
  - During exercise
  - Age and goal-based
  - Heart rate zones

- 📊 **Advanced Reports**
  - Charts and graphs
  - Progress tracking
  - Monthly comparisons

---

## 📝 Notes

### Versioning Strategy

```
MAJOR.MINOR.PATCH

MAJOR: Breaking changes
MINOR: New features (backward compatible)
PATCH: Bug fixes and minor improvements
```

### Support

- 🐛 **Bug Reports:** [GitHub Issues](../../issues)
- 💡 **Feature Requests:** [GitHub Issues](../../issues)
- 📧 **Email:** mahmoud.nour@developer.com

---

<div align="center">

**Developed with ❤️ by Mahmoud Nour | Software Developer**

**طُوّر بـ ❤️ بواسطة محمود نور | مطور برمجيات**

</div>
