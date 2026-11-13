/**
 * Tabya Website Frontend Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Character Counter for Translation Input
    const transInput = document.getElementById('englishInput');
    const charCountEl = document.getElementById('charCount');

    if (transInput && charCountEl) {
        function updateCount() {
            const count = transInput.value.length;
            charCountEl.textContent = `${count} / 300`;
        }
        transInput.addEventListener('input', updateCount);
        updateCount();
    }

    // 2. Quick Suggestion Chips Click
    const chips = document.querySelectorAll('.chip');
    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            const text = this.getAttribute('data-text');
            if (transInput && text) {
                transInput.value = text;
                if (charCountEl) {
                    charCountEl.textContent = `${text.length} / 300`;
                }
                transInput.focus();
            }
        });
    });

    // 3. Copy to Clipboard
    const copyBtns = document.querySelectorAll('.btn-copy');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetText = this.getAttribute('data-clipboard-text') ||
                               document.querySelector('.translated-text')?.innerText;
            if (targetText) {
                navigator.clipboard.writeText(targetText.trim()).then(() => {
                    showToast('Teks berhasil disalin ke clipboard!');
                    const icon = btn.querySelector('iconify-icon');
                    if (icon) {
                        const prevIcon = icon.getAttribute('icon');
                        icon.setAttribute('icon', 'lucide:check');
                        setTimeout(() => {
                            icon.setAttribute('icon', prevIcon || 'lucide:copy');
                        }, 1800);
                    }
                }).catch(err => {
                    console.error('Copy failed: ', err);
                });
            }
        });
    });

    // 4. Toast Notification
    function showToast(msg) {
        let toast = document.querySelector('.toast-notice');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-notice';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2500);
    }
});

