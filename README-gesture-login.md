# Gesture Recognition Service for PHP Library System

This is a Python Flask service that uses MediaPipe and OpenCV to recognize hand gestures for user login authentication. It is designed to work alongside the PHP library system as an external microservice.

## Features

- Real-time hand gesture recognition using webcam input
- Simple gesture logic (e.g., counting fingers)
- REST API endpoint to verify gesture tokens
- Integration with PHP backend via API calls

## Setup and Deployment

1. Ensure Python 3.8+ is installed on the server.
2. Install dependencies:
   ```
   pip install flask mediapipe opencv-python
   ```
3. Run the service:
   ```
   python gesture_recognition_service.py
   ```
4. The service will run on `http://localhost:5000` by default.

## API

- `POST /verify_gesture`
  - Request JSON:
    ```json
    {
      "userId": "user_id",
      "gestureToken": "token_from_recognition"
    }
    ```
  - Response JSON:
    ```json
    {
      "success": true|false,
      "message": "Optional message"
    }
    ```

## Integration with PHP Library System

- The PHP backend calls the `/verify_gesture` endpoint to validate gesture tokens.
- The PHP `GestureLogin` class handles communication and verification.
- Frontend login page integrates webcam capture and sends gesture tokens to the Python service.

## Notes

- This service should be secured and run in a trusted environment.
- Consider using HTTPS and authentication for API calls between PHP and Python services.
- Extend gesture recognition logic as needed for your use case.

## Support

For assistance, please contact the development team.
