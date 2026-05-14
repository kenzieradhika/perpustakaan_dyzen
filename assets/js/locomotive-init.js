/**
 * PERPUSTAKAAN DYZEN - Locomotive Scroll Initialization
 * Version: 1.0.0
 * Fungsi: Smooth scrolling dengan efek parallax
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initLocomotiveScroll();
});

let scrollInstance = null;

function initLocomotiveScroll() {
    // Check if LocomotiveScroll is available
    if (typeof LocomotiveScroll === 'undefined') {
        console.warn('LocomotiveScroll not loaded yet. Retrying in 500ms...');
        setTimeout(initLocomotiveScroll, 500);
        return;
    }
    
    // Get the scroll container
    const scrollContainer = document.querySelector('[data-scroll-container]');
    
    if (!scrollContainer) {
        console.warn('No [data-scroll-container] element found. Skipping Locomotive Scroll initialization.');
        return;
    }
    
    // Initialize Locomotive Scroll
    scrollInstance = new LocomotiveScroll({
        el: scrollContainer,
        smooth: true,
        multiplier: 0.8,
        smartphone: {
            smooth: true,
            multiplier: 0.6
        },
        tablet: {
            smooth: true,
            multiplier: 0.7
        },
        getDirection: true,
        lerp: 0.1,
        class: 'is-inview',
        firefoxMultiplier: 1,
        touchMultiplier: 2,
        resetNativeScroll: true
    });
    
    // ============================================
    // SYNC WITH GSAP SCROLLTRIGGER
    // ============================================
    
    if (typeof ScrollTrigger !== 'undefined') {
        // Update ScrollTrigger when locomotive scroll updates
        scrollInstance.on('scroll', (args) => {
            ScrollTrigger.update();
        });
        
        // Update ScrollTrigger on refresh
        ScrollTrigger.scrollerProxy(scrollContainer, {
            scrollTop(value) {
                return arguments.length ? 
                    scrollInstance.scrollTo(value, 0, 0) : 
                    scrollInstance.scroll.instance.scroll.y;
            },
            getBoundingClientRect() {
                return {
                    top: 0,
                    left: 0,
                    width: window.innerWidth,
                    height: window.innerHeight
                };
            },
            pinType: scrollContainer.style.transform ? 'transform' : 'fixed'
        });
        
        // Refresh ScrollTrigger after locomotive scroll is ready
        scrollInstance.on('call', () => {
            ScrollTrigger.refresh();
        });
        
        ScrollTrigger.addEventListener('refresh', () => scrollInstance.update());
        ScrollTrigger.refresh();
    }
    
    // ============================================
    // PARALLAX ELEMENTS INITIALIZATION
    // ============================================
    
    // Find all elements with data-scroll-speed attribute
    const parallaxElements = document.querySelectorAll('[data-scroll-speed]');
    parallaxElements.forEach(element => {
        const speed = parseFloat(element.getAttribute('data-scroll-speed')) || 0.5;
        
        if (scrollInstance) {
            scrollInstance.on('scroll', (args) => {
                const scrollY = args.scroll.y;
                const elementTop = element.offsetTop;
                const viewportHeight = window.innerHeight;
                
                // Calculate parallax offset
                const offset = (scrollY - (elementTop - viewportHeight)) * speed;
                const maxOffset = 200;
                const finalOffset = Math.min(Math.max(offset, -maxOffset), maxOffset);
                
                gsap.to(element, {
                    duration: 0.016,
                    y: finalOffset,
                    overwrite: true,
                    ease: 'none'
                });
            });
        }
    });
    
    // ============================================
    // FADE IN ON SCROLL (data-scroll)
    // ============================================
    
    const fadeElements = document.querySelectorAll('[data-scroll]');
    fadeElements.forEach(element => {
        const scrollClass = element.getAttribute('data-scroll-class') || 'is-inview';
        
        if (scrollInstance) {
            scrollInstance.on('scroll', () => {
                const rect = element.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                
                if (rect.top < windowHeight - 100 && rect.bottom > 100) {
                    element.classList.add(scrollClass);
                }
            });
        }
    });
    
    // ============================================
    // STICKY ELEMENTS HANDLING
    // ============================================
    
    const stickyElements = document.querySelectorAll('[data-scroll-sticky]');
    stickyElements.forEach(element => {
        const container = element.parentElement;
        if (container) {
            container.style.position = 'relative';
            
            if (scrollInstance) {
                scrollInstance.on('scroll', () => {
                    const rect = element.getBoundingClientRect();
                    const parentRect = container.getBoundingClientRect();
                    
                    if (rect.top <= 0 && parentRect.bottom > window.innerHeight) {
                        element.style.position = 'fixed';
                        element.style.top = '0';
                        element.style.width = rect.width + 'px';
                    } else if (parentRect.bottom <= window.innerHeight) {
                        element.style.position = 'absolute';
                        element.style.top = 'auto';
                        element.style.bottom = '0';
                    } else {
                        element.style.position = 'relative';
                        element.style.top = 'auto';
                    }
                });
            }
        }
    });
    
    // ============================================
    // UPDATE ON WINDOW RESIZE
    // ============================================
    
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (scrollInstance) {
                scrollInstance.update();
                ScrollTrigger.refresh();
            }
        }, 250);
    });
    
    // ============================================
    // UPDATE ON IMAGE LOAD
    // ============================================
    
    const allImages = document.querySelectorAll('img');
    let imagesLoaded = 0;
    
    function checkAllImagesLoaded() {
        imagesLoaded++;
        if (imagesLoaded === allImages.length && scrollInstance) {
            scrollInstance.update();
            ScrollTrigger.refresh();
        }
    }
    
    allImages.forEach(img => {
        if (img.complete) {
            checkAllImagesLoaded();
        } else {
            img.addEventListener('load', checkAllImagesLoaded);
            img.addEventListener('error', checkAllImagesLoaded);
        }
    });
    
    // ============================================
    // SMOOTH SCROLL TO ANCHOR LINKS
    // ============================================
    
    const anchorLinks = document.querySelectorAll('a[href^="#"]:not([href="#"])');
    anchorLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement && scrollInstance) {
                e.preventDefault();
                scrollInstance.scrollTo(targetElement, {
                    offset: -70,
                    duration: 1000,
                    easing: [0.25, 0.1, 0.25, 1]
                });
            }
        });
    });
    
    // ============================================
    // LOG INITIALIZATION
    // ============================================
    
    console.log('✅ Locomotive Scroll initialized successfully');
    
    // Dispatch custom event for other scripts
    window.dispatchEvent(new CustomEvent('locomotive:ready', { detail: { scroll: scrollInstance } }));
}

// ============================================
// PUBLIC METHODS
// ============================================

/**
 * Scroll to specific element or position
 * @param {HTMLElement|string|number} target - Element, selector, or scroll position
 * @param {number} offset - Offset from top (default: 0)
 * @param {number} duration - Animation duration (default: 1000)
 */
function scrollTo(target, offset = 0, duration = 1000) {
    if (!scrollInstance) return;
    
    let targetPosition;
    
    if (typeof target === 'number') {
        targetPosition = target;
    } else if (typeof target === 'string') {
        const element = document.querySelector(target);
        if (element) {
            targetPosition = element.offsetTop + offset;
        } else {
            return;
        }
    } else if (target instanceof HTMLElement) {
        targetPosition = target.offsetTop + offset;
    } else {
        return;
    }
    
    scrollInstance.scrollTo(targetPosition, {
        duration: duration,
        easing: [0.25, 0.1, 0.25, 1]
    });
}

/**
 * Update locomotive scroll (recalculate positions)
 */
function updateLocomotive() {
    if (scrollInstance) {
        scrollInstance.update();
    }
}

/**
 * Destroy locomotive scroll instance
 */
function destroyLocomotive() {
    if (scrollInstance) {
        scrollInstance.destroy();
        scrollInstance = null;
    }
}

// ============================================
// EXPORT FOR MODULE USE (if needed)
// ============================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        initLocomotiveScroll, 
        scrollTo, 
        updateLocomotive, 
        destroyLocomotive 
    };
}