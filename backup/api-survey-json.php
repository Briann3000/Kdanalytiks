<?php
// API for survey JSON operations
require_once 'rb.php';

// Connect to database
if (!defined('RB_SETUP')) {
    R::setup('sqlite:kmsurveytool.db');
    define('RB_SETUP', true);
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$surveyId = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit();
}

switch ($action) {
    case 'get':
        // Get survey JSON schema
        if (!$surveyId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Survey ID required']);
            exit();
        }

        $survey = R::load('surveys', $surveyId);
        if (!$survey->id) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Survey not found']);
            exit();
        }

        echo json_encode([
            'status' => 'success',
            'data' => json_decode($survey->json_schema),
            'survey_info' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'category' => $survey->category,
                'type' => $survey->type,
                'status' => $survey->status
            ]
        ]);
        break;

    case 'save':
        // Save/Update survey JSON
        if (!$surveyId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Survey ID required']);
            exit();
        }

        $json_schema = $_POST['json_schema'] ?? file_get_contents('php://input');

        if (!$json_schema) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON schema required']);
            exit();
        }

        // Validate JSON
        $decoded = json_decode($json_schema);
        if ($decoded === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format']);
            exit();
        }

        $survey = R::load('surveys', $surveyId);
        if (!$survey->id) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Survey not found']);
            exit();
        }

        $survey->json_schema = $json_schema;
        if (isset($_POST['status'])) {
            $survey->status = $_POST['status'];
        }

        R::store($survey);

        echo json_encode([
            'status' => 'success',
            'message' => 'Survey saved',
            'survey_id' => $survey->id
        ]);
        break;

    case 'submit':
        // Submit survey response
        if (!$surveyId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Survey ID required']);
            exit();
        }

        $survey = R::load('surveys', $surveyId);
        if (!$survey->id) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Survey not found']);
            exit();
        }

        $respondent_id = $_POST['respondent_id'] ?? null;
        $form_data = $_POST['data'] ?? file_get_contents('php://input');

        // Create response
        $response = R::dispense('responses');
        $response->survey_id = $surveyId;
        $response->respondent_id = $respondent_id;
        R::store($response);

        // Store answer data
        $answer = R::dispense('answers');
        $answer->response_id = $response->id;
        $answer->question_id = 0; // Not used for JSON surveys
        $answer->value = is_array($form_data) ? json_encode($form_data) : $form_data;
        R::store($answer);

        echo json_encode([
            'status' => 'success',
            'message' => 'Response recorded',
            'response_id' => $response->id
        ]);
        break;

    case 'list':
        // Get list of surveys for current user (admin or independent)
        session_start();

        if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
            exit();
        }

        if (isset($_SESSION['admin_logged_in'])) {
            $surveys = R::find('surveys', ' created_by = ? ORDER BY created_at DESC ', ['admin']);
        } else {
            $independent = R::findOne('independents', ' user_id = ? ', [$_SESSION['user']['id']]);
            if ($independent) {
                $surveys = R::find('surveys', ' independent_id = ? ORDER BY created_at DESC ', [$independent->id]);
            } else {
                $surveys = [];
            }
        }

        $data = [];
        foreach ($surveys as $survey) {
            $data[] = [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'category' => $survey->category,
                'type' => $survey->type,
                'status' => $survey->status,
                'created_at' => $survey->created_at
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}
?>