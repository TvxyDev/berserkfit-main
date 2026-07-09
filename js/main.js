document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (window.scrollY > 50) {
        header.classList.add('header-scrolled');
    } else {
        header.classList.remove('header-scrolled');
    }
});

const fadeInElements = document.querySelectorAll('.fade-in-element');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, {
    threshold: 0.1
});

fadeInElements.forEach(element => {
    observer.observe(element);
});

// Menu Hambúrguer
const menuToggle = document.getElementById('menuToggle');
const navMenu = document.getElementById('navMenu');
const menuOverlay = document.getElementById('menuOverlay');

function toggleMenu() {
    if (navMenu && menuToggle) {
        navMenu.classList.toggle('active');
        if (menuOverlay) {
            menuOverlay.classList.toggle('active');
        }
        const icon = menuToggle.querySelector('i');
        if (icon) {
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
                document.body.style.overflow = 'hidden';
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
                document.body.style.overflow = '';
            }
        }
    }
}

function closeMenu() {
    if (navMenu) {
        navMenu.classList.remove('active');
        if (menuOverlay) {
            menuOverlay.classList.remove('active');
        }
        const icon = menuToggle ? menuToggle.querySelector('i') : null;
        if (icon) {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
        document.body.style.overflow = '';
    }
}

if (menuToggle && navMenu) {
    menuToggle.addEventListener('click', toggleMenu);
    
    if (menuOverlay) {
        menuOverlay.addEventListener('click', closeMenu);
    }
    
    const navLinks = navMenu.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', closeMenu);
    });
}

// ── Custom Dynamic Modal Popups ──────────────────────────────────────────────
const injectStyles = () => {
    if (document.getElementById('custom-popup-styles')) return;
    const styles = `
        .custom-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 7, 32, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .custom-popup-overlay.active {
            opacity: 1;
        }
        .custom-popup-box {
            background: #1c0c3b;
            border: 2px solid #b8a8f5;
            border-radius: 16px;
            padding: 30px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            transform: scale(0.7);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: 'Inter', sans-serif;
        }
        .custom-popup-overlay.active .custom-popup-box {
            transform: scale(1);
        }
        .custom-popup-icon {
            font-size: 3rem;
            color: #b8a8f5;
            margin-bottom: 15px;
        }
        .custom-popup-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #ffffff;
            font-family: 'Syne', sans-serif;
        }
        .custom-popup-message {
            font-size: 1rem;
            color: #d1d5db;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .custom-popup-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .custom-popup-btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .custom-popup-btn-ok {
            background-color: #b8a8f5;
            color: #1c0c3b;
        }
        .custom-popup-btn-ok:hover {
            background-color: #a38ff1;
            transform: translateY(-2px);
        }
        .custom-popup-btn-cancel {
            background-color: transparent;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .custom-popup-btn-cancel:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    `;
    const styleEl = document.createElement('style');
    styleEl.id = 'custom-popup-styles';
    styleEl.innerHTML = styles;
    document.head.appendChild(styleEl);
};

window.showCustomAlert = function(title, message, callback) {
    injectStyles();
    const overlay = document.createElement('div');
    overlay.className = 'custom-popup-overlay';
    overlay.innerHTML = `
        <div class="custom-popup-box">
            <div class="custom-popup-icon"><i class="fas fa-info-circle"></i></div>
            <div class="custom-popup-title">${title}</div>
            <div class="custom-popup-message">${message}</div>
            <div class="custom-popup-buttons">
                <button class="custom-popup-btn custom-popup-btn-ok">Entendido</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    
    setTimeout(() => overlay.classList.add('active'), 10);
    
    overlay.querySelector('.custom-popup-btn-ok').onclick = () => {
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.remove();
            if (callback) callback();
        }, 300);
    };
};

window.showCustomConfirm = function(title, message, onConfirm, onCancel) {
    injectStyles();
    const overlay = document.createElement('div');
    overlay.className = 'custom-popup-overlay';
    overlay.innerHTML = `
        <div class="custom-popup-box">
            <div class="custom-popup-icon"><i class="fas fa-question-circle"></i></div>
            <div class="custom-popup-title">${title}</div>
            <div class="custom-popup-message">${message}</div>
            <div class="custom-popup-buttons">
                <button class="custom-popup-btn custom-popup-btn-cancel">Cancelar</button>
                <button class="custom-popup-btn custom-popup-btn-ok">Confirmar</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    
    setTimeout(() => overlay.classList.add('active'), 10);
    
    overlay.querySelector('.custom-popup-btn-ok').onclick = () => {
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.remove();
            if (onConfirm) onConfirm();
        }, 300);
    };
    
    overlay.querySelector('.custom-popup-btn-cancel').onclick = () => {
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.remove();
            if (onCancel) onCancel();
        }, 300);
    };
};

// Testimonial Carousel/Slider
document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.depoimentos-carousel-wrapper');
    if (!wrapper) return;

    const track = wrapper.querySelector('.carousel-track');
    const prevBtn = wrapper.querySelector('.prev-btn');
    const nextBtn = wrapper.querySelector('.next-btn');
    const dotsContainer = document.querySelector('.carousel-dots');
    
    let cards = Array.from(track.querySelectorAll('.cartao-depoimento'));
    if (cards.length === 0) return;

    let currentIndex = 0;
    let isDragging = false;
    let startX = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationId = 0;
    
    let visibleCards = 3;
    let maxIndex = 0;
    let gap = 30; // matching CSS gap
    
    const getVisibleCardsCount = () => {
        const width = window.innerWidth;
        if (width <= 768) return 1;
        if (width <= 992) return 2;
        return 3;
    };

    const updateSliderConfig = () => {
        visibleCards = getVisibleCardsCount();
        maxIndex = Math.max(0, cards.length - visibleCards);
        
        // Reset index if it goes beyond max
        if (currentIndex > maxIndex) {
            currentIndex = maxIndex;
        }
        
        // Render dots
        if (dotsContainer) {
            dotsContainer.innerHTML = '';
            if (maxIndex > 0) {
                for (let i = 0; i <= maxIndex; i++) {
                    const dot = document.createElement('button');
                    dot.className = `carousel-dot ${i === currentIndex ? 'active' : ''}`;
                    dot.setAttribute('aria-label', `Slide ${i + 1}`);
                    dot.addEventListener('click', () => {
                        currentIndex = i;
                        slideToIndex();
                    });
                    dotsContainer.appendChild(dot);
                }
                dotsContainer.style.display = 'flex';
            } else {
                dotsContainer.style.display = 'none';
            }
        }
        
        // Hide/Show or Enable/Disable Nav buttons
        if (maxIndex === 0) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
        } else {
            if (prevBtn) prevBtn.style.display = 'flex';
            if (nextBtn) nextBtn.style.display = 'flex';
        }
        
        slideToIndex(false); // Snap without transition on config update
    };

    const getCardWidth = () => {
        if (cards.length === 0) return 0;
        return cards[0].getBoundingClientRect().width;
    };

    const slideToIndex = (withTransition = true) => {
        if (cards.length === 0) return;
        
        const cardWidth = getCardWidth();
        const offset = -currentIndex * (cardWidth + gap);
        
        if (withTransition) {
            track.style.transition = 'transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        } else {
            track.style.transition = 'none';
        }
        
        track.style.transform = `translateX(${offset}px)`;
        prevTranslate = offset;
        
        // Update disabled states
        if (prevBtn) prevBtn.disabled = currentIndex === 0;
        if (nextBtn) nextBtn.disabled = currentIndex === maxIndex;
        
        // Update dots
        if (dotsContainer) {
            const dots = dotsContainer.querySelectorAll('.carousel-dot');
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });
        }
    };

    // Nav button event listeners
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                slideToIndex();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (currentIndex < maxIndex) {
                currentIndex++;
                slideToIndex();
            }
        });
    }

    // Touch & Mouse Dragging logic
    const touchStart = () => {
        return function(event) {
            if (maxIndex === 0) return;
            isDragging = true;
            track.classList.add('dragging');
            
            startX = getPositionX(event);
            animationId = requestAnimationFrame(animation);
        };
    };

    const touchMove = (event) => {
        if (!isDragging) return;
        const currentX = getPositionX(event);
        const diffX = currentX - startX;
        currentTranslate = prevTranslate + diffX;
    };

    const touchEnd = () => {
        if (!isDragging) return;
        isDragging = false;
        cancelAnimationFrame(animationId);
        track.classList.remove('dragging');
        
        const movedBy = currentTranslate - prevTranslate;
        const cardWidth = getCardWidth();
        const threshold = (cardWidth + gap) / 4; // 25% of slide width to trigger slide change
        
        if (movedBy < -threshold && currentIndex < maxIndex) {
            currentIndex++;
        } else if (movedBy > threshold && currentIndex > 0) {
            currentIndex--;
        }
        
        slideToIndex();
    };

    const getPositionX = (event) => {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    };

    const animation = () => {
        if (isDragging) {
            // Constrain drag boundaries with resistance
            const cardWidth = getCardWidth();
            const minTranslate = -maxIndex * (cardWidth + gap);
            const maxTranslate = 0;
            
            let tempTranslate = currentTranslate;
            if (tempTranslate > maxTranslate) {
                tempTranslate = maxTranslate + (tempTranslate - maxTranslate) * 0.3; // resistance
            } else if (tempTranslate < minTranslate) {
                tempTranslate = minTranslate + (tempTranslate - minTranslate) * 0.3; // resistance
            }
            
            track.style.transform = `translateX(${tempTranslate}px)`;
            animationId = requestAnimationFrame(animation);
        }
    };

    // Add drag listeners
    track.addEventListener('touchstart', touchStart(), { passive: true });
    track.addEventListener('touchmove', touchMove, { passive: true });
    track.addEventListener('touchend', touchEnd);
    
    track.addEventListener('mousedown', touchStart());
    track.addEventListener('mousemove', touchMove);
    track.addEventListener('mouseup', touchEnd);
    track.addEventListener('mouseleave', touchEnd);

    // Prevent dragging image default action
    track.querySelectorAll('img').forEach(img => {
        img.addEventListener('dragstart', (e) => e.preventDefault());
    });

    // Initialize
    updateSliderConfig();
    window.addEventListener('resize', updateSliderConfig);
});

// Newsletter Form Submission Handler
document.addEventListener('DOMContentLoaded', () => {
    const newsletterForm = document.getElementById('formNewsletter');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const emailInput = document.getElementById('newsletterEmail');
            if (!emailInput) return;
            const email = emailInput.value.trim();
            if (!email) return;

            const button = newsletterForm.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'A processar...';

            fetch('subscrever_newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email })
            })
            .then(res => res.json())
            .then(data => {
                button.disabled = false;
                button.textContent = originalText;
                
                if (data.status === 'success') {
                    emailInput.value = '';
                    if (window.showCustomAlert) {
                        window.showCustomAlert('Newsletter ⚔️', data.message);
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (window.showCustomAlert) {
                        window.showCustomAlert('Erro ❌', data.message);
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(err => {
                button.disabled = false;
                button.textContent = originalText;
                console.error(err);
                if (window.showCustomAlert) {
                    window.showCustomAlert('Erro ❌', 'Ocorreu um erro de rede. Tenta novamente mais tarde.');
                } else {
                    alert('Ocorreu um erro de rede. Tenta novamente mais tarde.');
                }
            });
        });
    }
});

