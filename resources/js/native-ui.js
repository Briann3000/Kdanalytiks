import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
import { StatusBar, Style } from '@capacitor/status-bar';

const initNativeUI = async () => {
    if (Capacitor.isNativePlatform()) {
        try {
            // Set Status Bar to light mode with a white background
            // Ensure overlays is false so it doesn't cover content
            await StatusBar.setOverlaysWebView({ overlay: false });
            await StatusBar.setStyle({ style: Style.Light });
            await StatusBar.setBackgroundColor({ color: '#ffffff' });
            
            // Hide the splash screen once JS has loaded
            await SplashScreen.hide();
        } catch (e) {
            console.warn('Native UI initialization error:', e);
        }
    }
};

// Enhancement: Handle file inputs with Native Camera
const interceptFileInputs = () => {
    if (!Capacitor.isNativePlatform()) return;

    document.addEventListener('click', async (e) => {
        const fileInput = e.target.closest('input[type="file"]');
        if (fileInput && !fileInput.dataset.nativeHandled) {
            e.preventDefault();
            try {
                const { Camera } = await import('@capacitor/camera');
                const image = await Camera.getPhoto({
                    quality: 90,
                    allowEditing: true,
                    resultType: 'uri'
                });
                
                // Show the user we got the image (Logic to attach to form goes here)
                console.log('Image captured:', image.webPath);
                alert('Image captured successfully!');
            } catch (err) {
                console.log('Camera cancelled or failed');
            }
        }
    });
};

// Initialize when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initNativeUI();
    interceptFileInputs();
});
