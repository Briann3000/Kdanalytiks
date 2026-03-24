import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';

const initPushNotifications = async () => {
    if (Capacitor.isNativePlatform()) {
        try {
            // Request permission to use push notifications
            // iOS will prompt user and return if they granted permission or not
            // Android will just grant without prompting
            const permStatus = await PushNotifications.requestPermissions();

            if (permStatus.receive === 'granted') {
                // Register with Apple / Google to receive push via APNS/FCM
                await PushNotifications.register();
            } else {
                console.warn('User denied push notification permission');
            }

            // On success, we should be able to receive notifications
            PushNotifications.addListener('registration', (token) => {
                console.log('Push registration success, token: ' + token.value);
                // TODO: Send token to your Laravel backend to store against the user profile
            });

            // Some issue with our setup and push will not work
            PushNotifications.addListener('registrationError', (error) => {
                console.error('Error on push registration: ' + JSON.stringify(error));
            });

            // Show us the notification payload if the app is open on our device
            PushNotifications.addListener('pushNotificationReceived', (notification) => {
                console.log('Push received: ' + JSON.stringify(notification));
                alert('New Notification: ' + notification.title);
            });

            // Method called when tapping on a notification
            PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
                console.log('Push action performed: ' + JSON.stringify(notification));
            });
        } catch (e) {
            console.warn('Push Notifications initialization error:', e);
        }
    }
};

// Start listening after DOM is ready
document.addEventListener('DOMContentLoaded', initPushNotifications);
