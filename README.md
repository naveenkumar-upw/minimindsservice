# Push Notification Setup Guide

## Backend Setup

1. Create a Firebase Project:
   - Go to [Firebase Console](https://console.firebase.google.com/)
   - Create a new project
   - Navigate to Project Settings > Service Accounts
   - Generate a new private key and download the JSON file
   - Rename it to `firebase-credentials.json` and place it in `includes/firebase/` directory

2. Update Firebase Configuration:
   - Open `includes/firebase/config.php`
   - Replace `your-project-id` with your actual Firebase project ID

3. Install Dependencies:
```bash
composer require google/cloud-firestore
```

## Database Setup

The system requires a `device_tokens` table for storing Android device tokens. Run the updated schema.sql file to create this table:

```bash
mysql -u your_username -p < includes/schema.sql
```

## API Endpoints

### Register Device Token
```http
POST /api_device.php
Content-Type: application/json

{
    "device_token": "your-fcm-token"
}
```

### Unregister Device Token
```http
DELETE /api_device.php
Content-Type: application/json

{
    "device_token": "your-fcm-token"
}
```

## Android App Integration

1. Add Firebase to your Android app:
```gradle
implementation 'com.google.firebase:firebase-messaging:23.1.0'
```

2. Update your Android app's manifest:
```xml
<service android:name=".MyFirebaseMessagingService"
    android:exported="false">
    <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT" />
    </intent-filter>
</service>
```

3. Create a Firebase Messaging Service:
```java
public class MyFirebaseMessagingService extends FirebaseMessagingService {
    @Override
    public void onNewToken(@NonNull String token) {
        // Send token to server
        registerToken(token);
    }

    @Override
    public void onMessageReceived(@NonNull RemoteMessage message) {
        // Handle received notification
        if (message.getNotification() != null) {
            String title = message.getNotification().getTitle();
            String body = message.getNotification().getBody();
            // Show notification
            showNotification(title, body);
        }
    }

    private void registerToken(String token) {
        // Send HTTP POST request to your server
        // Endpoint: /api_device.php
        // Body: {"device_token": token}
    }

    private void showNotification(String title, String body) {
        // Create and show notification using NotificationCompat.Builder
    }
}
```

4. Register token when app starts:
```java
FirebaseMessaging.getInstance().getToken()
    .addOnCompleteListener(task -> {
        if (task.isSuccessful() && task.getResult() != null) {
            String token = task.getResult();
            // Send token to server
            registerToken(token);
        }
    });
```

## Testing

1. Add a new story through the web interface
2. A push notification will be sent to all registered Android devices
3. The notification will show the story title and a message

## Security Notes

1. Keep your Firebase credentials secure and never commit them to version control
2. Implement proper authentication for token registration/unregistration
3. Consider implementing rate limiting for API endpoints
4. Use HTTPS for all API communications
