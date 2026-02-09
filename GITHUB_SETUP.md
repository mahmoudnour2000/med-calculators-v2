# 🚀 How to Push to GitHub / كيفية الرفع على GitHub

<div dir="rtl">

## خطوات رفع الريبو على GitHub:

### 1. إنشاء ريبو جديد على GitHub:
1. اذهب إلى [GitHub.com](https://github.com)
2. اضغط على **"+"** ← **"New repository"**
3. ضع الاسم: `med-calculators-v2` (أو أي اسم تفضله)
4. اختر: **Public** أو **Private**
5. **لا تضف** README أو .gitignore أو License (موجودين بالفعل)
6. اضغط **"Create repository"**

### 2. ربط الريبو المحلي بـ GitHub:

افتح Terminal/CMD في مجلد الريبو وشغّل الأوامر التالية:

```bash
# استبدل "yourusername" باسم حسابك على GitHub
git remote add origin https://github.com/yourusername/med-calculators-v2.git

# أو إذا كنت تستخدم SSH:
git remote add origin git@github.com:yourusername/med-calculators-v2.git
```

### 3. رفع الملفات:

```bash
git branch -M main
git push -u origin main
```

### 4. التحقق:
- افتح صفحة الريبو على GitHub
- تأكد من ظهور جميع الملفات
- تأكد من ظهور ملف README بشكل احترافي

---

## ملاحظات مهمة:

### إذا طلب منك GitHub بيانات الدخول:
```bash
# إعداد بياناتك (مرة واحدة فقط):
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### إذا كنت تستخدم Personal Access Token:
1. اذهب إلى: **Settings** ← **Developer settings** ← **Personal access tokens**
2. اختر **Tokens (classic)** ← **Generate new token**
3. اختر الصلاحيات: `repo` (full control)
4. انسخ الـ Token واستخدمه بدلاً من كلمة المرور

### تحديث الريبو بعد أي تعديلات:
```bash
git add .
git commit -m "وصف التعديل"
git push
```

</div>

---

## English Instructions:

### 1. Create a New Repository on GitHub:
1. Go to [GitHub.com](https://github.com)
2. Click **"+"** → **"New repository"**
3. Name it: `med-calculators-v2` (or your preferred name)
4. Choose: **Public** or **Private**
5. **Don't add** README, .gitignore, or License (already exist)
6. Click **"Create repository"**

### 2. Connect Local Repository to GitHub:

Open Terminal/CMD in the repository folder and run:

```bash
# Replace "yourusername" with your GitHub username
git remote add origin https://github.com/yourusername/med-calculators-v2.git

# Or if using SSH:
git remote add origin git@github.com:yourusername/med-calculators-v2.git
```

### 3. Push Files:

```bash
git branch -M main
git push -u origin main
```

### 4. Verify:
- Open the repository page on GitHub
- Ensure all files are visible
- Confirm README displays professionally

---

## Important Notes:

### If GitHub asks for credentials:
```bash
# Configure your details (one time only):
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### If using Personal Access Token:
1. Go to: **Settings** → **Developer settings** → **Personal access tokens**
2. Choose **Tokens (classic)** → **Generate new token**
3. Select scopes: `repo` (full control)
4. Copy the Token and use it instead of password

### Update repository after changes:
```bash
git add .
git commit -m "Description of changes"
git push
```

---

## 📝 Quick Commands Reference:

```bash
# View status
git status

# View commit history
git log --oneline

# View remote URL
git remote -v

# Create new branch
git checkout -b feature-name

# Switch branches
git checkout main

# Pull latest changes
git pull origin main
```

---

<div align="center">

**Happy Coding! 🚀**

**برمجة سعيدة! 🚀**

</div>
