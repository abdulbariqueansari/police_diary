// Sidebar Toggle Function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Close sidebar on outside click for mobile
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.querySelector('.menu-toggle');
        if (sidebar && menuToggle && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    }
});

// Close sidebar on resize if screen becomes larger
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.remove('open');
        }
    }
});

// Voice Recognition Setup
let recognition = null;
let currentTextareaId = null;

function initVoiceRecognition(language) {
    if (('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) && !recognition) {
        const SpeechRecognition = window.webkitSpeechRecognition || window.SpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        
        // Set language based on user preference
        const langMap = {
            'hi': 'hi-IN', 'bn': 'bn-BD', 'ta': 'ta-IN', 'te': 'te-IN',
            'mr': 'mr-IN', 'gu': 'gu-IN', 'kn': 'kn-IN', 'ml': 'ml-IN',
            'pa': 'pa-IN', 'en': 'en-IN'
        };
        recognition.lang = langMap[language] || 'en-IN';
        
        recognition.onresult = async function(event) {
            const transcript = event.results[0][0].transcript;
            const textarea = document.getElementById(currentTextareaId) || document.getElementsByName(currentTextareaId)[0];
            const voiceBtn = document.querySelector('.voice-btn.recording');
            
            try {
                const sourceLang = recognition.lang.split('-')[0];
                let translatedText = transcript;
                
                if (sourceLang !== 'en') {
                    const response = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=${sourceLang}&tl=en&dt=t&q=${encodeURIComponent(transcript)}`);
                    const data = await response.json();
                    translatedText = data[0].map(item => item[0]).join('');
                }
                
                if (textarea) {
                    textarea.value = textarea.value ? textarea.value + ' ' + translatedText : translatedText;
                }
            } catch (error) {
                console.error("Translation error:", error);
                if (textarea) {
                    textarea.value = textarea.value ? textarea.value + ' ' + transcript : transcript;
                }
            } finally {
                if (voiceBtn) voiceBtn.classList.remove('recording');
            }
        };
        
        recognition.onerror = function() {
            const voiceBtn = document.querySelector('.voice-btn.recording');
            if (voiceBtn) voiceBtn.classList.remove('recording');
            alert('Voice recognition error. Please try again.');
        };
        
        recognition.onend = function() {
            const voiceBtn = document.querySelector('.voice-btn.recording');
            if (voiceBtn) voiceBtn.classList.remove('recording');
        };
    }
}

function startVoiceRecognition(textareaId) {
    if (!recognition) {
        alert('Voice recognition is not supported in your browser. Please use Google Chrome.');
        return;
    }
    currentTextareaId = textareaId;
    const voiceBtn = event.currentTarget;
    voiceBtn.classList.add('recording');
    recognition.start();
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '#e5e7eb';
        }
    });
    
    return isValid;
}

// Date Range Validation
function validateDateRange(fromDateId, toDateId) {
    const fromDate = document.getElementById(fromDateId);
    const toDate = document.getElementById(toDateId);
    
    if (fromDate && toDate && fromDate.value && toDate.value) {
        if (fromDate.value > toDate.value) {
            alert('From date cannot be greater than To date');
            return false;
        }
    }
    return true;
}

// Confirmation Dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (callback) callback();
        return true;
    }
    return false;
}

// Loader Show/Hide
function showLoader() {
    let loader = document.getElementById('global-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'global-loader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
        `;
        loader.innerHTML = `
            <div class="spinner" style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.3); border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="color: white;">Processing...</p>
        `;
        document.body.appendChild(loader);
        
        // Add spin animation if not exists
        if (!document.querySelector('#spin-style')) {
            const style = document.createElement('style');
            style.id = 'spin-style';
            style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
            document.head.appendChild(style);
        }
    }
    loader.style.display = 'flex';
}

function hideLoader() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

// Export Functions for global use
window.toggleSidebar = toggleSidebar;
window.startVoiceRecognition = startVoiceRecognition;
window.initVoiceRecognition = initVoiceRecognition;
window.validateForm = validateForm;
window.validateDateRange = validateDateRange;
window.confirmAction = confirmAction;
window.showLoader = showLoader;
window.hideLoader = hideLoader;
  
