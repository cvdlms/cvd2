# CVD Learning Management System - UI Design Guidelines

## 📋 Tổng Quan
Document này định nghĩa các tiêu chuẩn thiết kế UI/UX cho hệ thống CVD LMS. Mục tiêu là tạo ra giao diện cao cấp, chuyên nghiệp và nhất quán trên toàn bộ hệ thống.

---

## 🎨 Color Palette

### Primary Gradients
```css
/* Primary Purple Gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Success Green Gradient */
background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);

/* Danger Red Gradient */
background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);

/* Warning Orange Gradient */
background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);

/* Info Blue Gradient */
background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
```

### Neutral Colors
```css
/* Background Gradients */
.bg-light-gradient {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.bg-subtle-gradient {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}
```

---

## ✨ Animation Standards

### Timing Functions
```css
/* Smooth, Professional Transitions */
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

/* Bouncy Effect for Selected States */
transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);

/* Quick Feedback */
transition: all 0.2s ease;
```

### Keyframe Animations
```css
/* Slide In Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Pulse Animation */
@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0);
    }
}

/* Usage */
.animated-element {
    animation: slideIn 0.3s ease;
}
```

---

## 🎯 Interactive Elements

### Hover Effects
```css
/* Cards & Containers */
.interactive-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.interactive-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
}

/* Buttons */
.btn-gradient:hover {
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    transform: translateY(-1px);
}
```

### Selected States
```css
/* Tag/Card Selection */
.selectable-item {
    border: 2px solid #e9ecef;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.selectable-item.selected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}
```

---

## 📦 Component Patterns

### 1. Premium Tag Selector

**Use Case**: Multi-select với visual feedback rõ ràng

**HTML Structure**:
```html
<div class="class-tags-grid">
    <div class="class-tag" onclick="toggleTag(this)">
        <input type="checkbox" style="display: none;">
        <i class="bi bi-check-circle-fill tag-check"></i>
        <span class="tag-code">9A1</span>
        <span class="tag-name">Lớp 9A1 - Tin học</span>
    </div>
</div>
```

**CSS**:
```css
.class-tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
}

.class-tag {
    position: relative;
    padding: 12px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.class-tag:hover {
    transform: translateY(-3px);
    border-color: #667eea;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
}

.class-tag.selected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.tag-check {
    position: absolute;
    top: 6px;
    right: 6px;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.class-tag.selected .tag-check {
    opacity: 1;
    transform: scale(1);
}
```

### 2. Selected Items Display

**Use Case**: Hiển thị các items đã chọn với badges

**HTML**:
```html
<div class="selected-classes-display">
    <div class="selected-class-badge">
        <span><strong>9A1</strong> Lớp 9A1</span>
        <i class="bi bi-x-circle remove-icon"></i>
    </div>
</div>
```

**CSS**:
```css
.selected-classes-display {
    min-height: 60px;
    max-height: 120px;
    overflow-y: auto;
    padding: 10px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.selected-class-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    animation: slideIn 0.3s ease;
    cursor: pointer;
    transition: all 0.2s ease;
}

.selected-class-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
```

### 3. Status Badges

**CSS**:
```css
.badge-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.badge-expired {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
}

.badge-pending {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    color: white;
}
```

### 4. Counter Badge

**HTML**:
```html
<span class="badge bg-primary class-counter">3 lớp</span>
```

**CSS**:
```css
.class-counter {
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 12px;
    animation: pulse 2s infinite;
}
```

---

## 🎭 Custom Scrollbars

```css
.custom-scroll::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.custom-scroll::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}
```

---

## 🔘 Button Styles

### Gradient Buttons
```css
.btn-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-gradient-primary:hover {
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
    transform: translateY(-1px);
}

.btn-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    color: white;
}

.btn-gradient-danger {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    border: none;
    color: white;
}

.btn-gradient-info {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    border: none;
    color: white;
}
```

---

## 📐 Layout Patterns

### Card Styles
```css
.selection-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.selection-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.selection-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border-radius: 12px 12px 0 0 !important;
}
```

### Page Header
```css
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}
```

---

## 📱 Responsive Grid

```css
/* Auto-fill grid responsive */
.grid-auto-fill {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
}

/* Responsive columns */
@media (max-width: 768px) {
    .grid-auto-fill {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
}
```

---

## 💡 Best Practices

### 1. **Visual Hierarchy**
- Sử dụng gradient để tạo depth và hierarchy
- Bold text cho thông tin quan trọng
- Opacity 0.8 cho secondary text

### 2. **Feedback & State**
- Luôn có hover state cho interactive elements
- Selected state phải rõ ràng (color change, checkmark)
- Disabled state với opacity 0.5

### 3. **Animations**
- Transition duration: 0.2s - 0.3s
- Sử dụng cubic-bezier cho smooth motion
- Tránh animations quá dài (> 0.5s)

### 4. **Spacing**
- Gap: 8px - 12px cho grid items
- Padding: 10px - 15px cho containers
- Margin bottom: 1rem - 2rem cho sections

### 5. **Typography**
- Font size giảm dần: 1rem → 0.875rem → 0.75rem
- Font weight: 400 (normal) → 500 (medium) → 600-700 (bold)
- Letter spacing: 0.5px cho uppercase text

### 6. **Shadows**
- Subtle: `0 2px 8px rgba(0,0,0,0.1)`
- Medium: `0 4px 15px rgba(0,0,0,0.08)`
- Elevated: `0 6px 20px rgba(102, 126, 234, 0.15)`
- Selected: `0 4px 16px rgba(102, 126, 234, 0.4)`

---

## 🚀 JavaScript Patterns

### Toggle Selection with Visual Update
```javascript
function toggleTag(element) {
    const checkbox = element.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        element.classList.add('selected');
    } else {
        element.classList.remove('selected');
    }
    
    updateDisplay();
}
```

### Update Counter with Animation
```javascript
function updateCounter(count) {
    const counter = document.getElementById('counter');
    counter.textContent = count + ' items';
    
    // Restart animation
    counter.style.animation = 'none';
    setTimeout(() => counter.style.animation = '', 10);
}
```

### Select All/Clear All
```javascript
function selectAll(selector) {
    document.querySelectorAll(selector).forEach(el => {
        el.checked = true;
        el.closest('.selectable-item').classList.add('selected');
    });
    updateDisplay();
}

function clearAll(selector) {
    document.querySelectorAll(selector).forEach(el => {
        el.checked = false;
        el.closest('.selectable-item').classList.remove('selected');
    });
    updateDisplay();
}
```

---

## 📝 Checklist Khi Tạo UI Mới

- [ ] Có gradient backgrounds cho các elements quan trọng
- [ ] Hover states với transform và shadow
- [ ] Selected states rõ ràng với checkmark/icon
- [ ] Smooth transitions (0.2s - 0.3s)
- [ ] Custom scrollbar nếu có overflow
- [ ] Responsive grid layout
- [ ] Badge/counter animations
- [ ] Visual feedback cho user actions
- [ ] Consistent spacing (gaps, paddings)
- [ ] Box shadows cho depth
- [ ] Border radius (8px - 12px)
- [ ] Icon integration (Bootstrap Icons)

---

## 🎨 Example: Complete Implementation

Xem `cvd2/teacher/manage_assignments.php` để tham khảo implementation hoàn chỉnh của:
- Premium Tag Selector
- Selected Classes Display
- Counter Badge with Animation
- Multi-select with Visual Feedback

---

**Last Updated**: February 6, 2026  
**Version**: 1.0  
**Maintained by**: CVD Development Team
