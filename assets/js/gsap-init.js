/**
 * PERPUSTAKAAN DYZEN - GSAP Initialization
 * Version: 1.0.0
 * Fungsi: Animasi scroll, hover effects, dan entrance animations
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initGSAPAnimations();
});

function initGSAPAnimations() {
    // Register ScrollTrigger plugin
    gsap.registerPlugin(ScrollTrigger);
    
    // ============================================
    // HERO SECTION ANIMATIONS
    // ============================================
    
    // Hero text animation - fade in dari bawah
    gsap.from('.hero-content h1', {
        duration: 1,
        y: 100,
        opacity: 0,
        ease: 'power3.out',
        delay: 0.3
    });
    
    gsap.from('.hero-content p', {
        duration: 0.8,
        y: 50,
        opacity: 0,
        ease: 'power3.out',
        delay: 0.6
    });
    
    gsap.from('.hero-content .btn-primary, .hero-content .btn-outline', {
        duration: 0.6,
        scale: 0.8,
        opacity: 0,
        stagger: 0.2,
        ease: 'back.out(1.2)',
        delay: 0.9
    });
    
    // Hero illustration floating animation (continuous)
    gsap.to('.animate-float', {
        duration: 3,
        y: -20,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
    });
    
    // ============================================
    // STATS COUNTER ANIMATION (with GSAP)
    // ============================================
    
    const statNumbers = document.querySelectorAll('.stats-number');
    statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target'));
        if (target) {
            gsap.fromTo(stat, 
                { innerText: 0 },
                {
                    duration: 2,
                    innerText: target,
                    snap: { innerText: 1 },
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: stat,
                        start: 'top 80%',
                        toggleActions: 'play none none reverse'
                    },
                    onUpdate: function() {
                        stat.innerText = Math.floor(this.targets()[0].innerText);
                    }
                }
            );
        }
    });
    
    // ============================================
    // FEATURED BOOKS CARDS ANIMATION
    // ============================================
    
    // Stagger animation for book cards
    gsap.from('.book-card', {
        scrollTrigger: {
            trigger: '#koleksi',
            start: 'top 80%',
            end: 'bottom 20%',
            scrub: 1,
            toggleActions: 'play none none reverse'
        },
        y: 100,
        opacity: 0,
        rotationX: 20,
        stagger: 0.1,
        duration: 1,
        ease: 'power2.out'
    });
    
    // Individual card hover animation (handled by CSS, but we add GSAP for extra effect)
    const bookCards = document.querySelectorAll('.book-card');
    bookCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                duration: 0.3,
                y: -10,
                scale: 1.02,
                boxShadow: '0 20px 40px rgba(0,0,0,0.15)',
                ease: 'power2.out'
            });
        });
        
        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                duration: 0.3,
                y: 0,
                scale: 1,
                boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
                ease: 'power2.out'
            });
        });
    });
    
    // ============================================
    // HOW TO BORROW STEPS ANIMATION
    // ============================================
    
    gsap.from('#carapinjam .w-20', {
        scrollTrigger: {
            trigger: '#carapinjam',
            start: 'top 70%',
            toggleActions: 'play none none reverse'
        },
        scale: 0,
        opacity: 0,
        rotation: 360,
        stagger: 0.2,
        duration: 0.8,
        ease: 'back.out(1.5)'
    });
    
    gsap.from('#carapinjam h3, #carapinjam p', {
        scrollTrigger: {
            trigger: '#carapinjam',
            start: 'top 70%',
            toggleActions: 'play none none reverse'
        },
        y: 30,
        opacity: 0,
        stagger: 0.15,
        duration: 0.6,
        ease: 'power2.out'
    });
    
    // ============================================
    // TESTIMONIAL SECTION (if exists)
    // ============================================
    
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    if (testimonialCards.length > 0) {
        gsap.from(testimonialCards, {
            scrollTrigger: {
                trigger: '.testimonial-section',
                start: 'top 70%',
                toggleActions: 'play none none reverse'
            },
            x: -50,
            opacity: 0,
            stagger: 0.15,
            duration: 0.8,
            ease: 'power2.out'
        });
    }
    
    // ============================================
    // CTA SECTION ANIMATION
    // ============================================
    
    gsap.from('.cta-section h2, .cta-section p, .cta-section .btn-primary', {
        scrollTrigger: {
            trigger: '.cta-section',
            start: 'top 80%',
            toggleActions: 'play none none reverse'
        },
        y: 50,
        opacity: 0,
        stagger: 0.2,
        duration: 0.8,
        ease: 'power2.out'
    });
    
    // ============================================
    // FOOTER ANIMATION
    // ============================================
    
    gsap.from('footer .grid > div', {
        scrollTrigger: {
            trigger: 'footer',
            start: 'top 90%',
            toggleActions: 'play none none reverse'
        },
        y: 30,
        opacity: 0,
        stagger: 0.1,
        duration: 0.6,
        ease: 'power2.out'
    });
    
    // ============================================
    // PARALLAX EFFECT FOR HERO BACKGROUND
    // ============================================
    
    if (document.querySelector('.hero-video')) {
        gsap.to('.hero-video', {
            scrollTrigger: {
                trigger: '#home',
                start: 'top top',
                end: 'bottom top',
                scrub: 1
            },
            scale: 1.1,
            ease: 'none'
        });
    }
    
    // ============================================
    // MARQUEE TEXT ANIMATION (CSS already handles, but we add GSAP for smoothness)
    // ============================================
    
    const marqueeContainers = document.querySelectorAll('.marquee-fast');
    marqueeContainers.forEach(container => {
        const content = container.querySelector('.marquee-content-fast, .marquee-content-reverse-fast');
        if (content) {
            // Ensure smooth animation
            content.style.animationPlayState = 'running';
        }
    });
    
    // ============================================
    // SCROLL PROGRESS INDICATOR (optional)
    // ============================================
    
    // Create scroll progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #09637E, #088395);
        z-index: 9999;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);
    
    gsap.to(progressBar, {
        scrollTrigger: {
            trigger: document.body,
            start: 'top top',
            end: 'bottom bottom',
            scrub: 0.3,
            onUpdate: (self) => {
                progressBar.style.width = (self.progress * 100) + '%';
            }
        },
        ease: 'none'
    });
    
    // ============================================
    // SMOOTH REVEAL FOR ALL SECTIONS
    // ============================================
    
    // Reveal sections on scroll
    const sections = document.querySelectorAll('section');
    sections.forEach(section => {
        gsap.from(section, {
            scrollTrigger: {
                trigger: section,
                start: 'top 85%',
                toggleActions: 'play none none reverse'
            },
            opacity: 0,
            y: 30,
            duration: 0.8,
            ease: 'power2.out'
        });
    });
    
    // ============================================
    // NUMBER COUNTER FOR STATS (alternative with GSAP)
    // ============================================
    
    function animateNumber(element, start, end, duration) {
        if (!element) return;
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                element.textContent = end.toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    }
    
    // Observe counters with Intersection Observer
    const counters = document.querySelectorAll('.counter, .stats-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.getAttribute('data-target'));
                if (target && !entry.target.classList.contains('animated')) {
                    animateNumber(entry.target, 0, target, 2000);
                    entry.target.classList.add('animated');
                }
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
}

// ============================================
// RESIZE HANDLER
// ============================================

window.addEventListener('resize', function() {
    // Refresh ScrollTrigger on resize
    if (typeof ScrollTrigger !== 'undefined') {
        ScrollTrigger.refresh();
    }
});

// ============================================
// EXPORT FOR MODULE USE (if needed)
// ============================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initGSAPAnimations };
}