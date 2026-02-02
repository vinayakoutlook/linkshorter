<?php
require_once __DIR__ . '/env.php';
session_start();
header('Content-Type: application/json');

$dataFile = env('DATA_FILE', __DIR__ . '/data/links.txt');

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure data directory exists
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Helper functions
function loadLinks() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        return [];
    }
    $content = file_get_contents($dataFile);
    return $content ? json_decode($content, true) : [];
}

function saveLinks($links) {
    global $dataFile;
    return file_put_contents($dataFile, json_encode($links, JSON_PRETTY_PRINT));
}

function generateId() {
    return uniqid('link_', true);
}

function shortenUrl($url) {
    $apiUrl = env('API_URL') . '?api=' . env('API_KEY') . '&url=' . urlencode($url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'CURL Error: ' . $error];
    }
    
    $result = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the raw response for debugging (optional: write to a file)
            // file_put_contents(__DIR__ . '/api_error.log', $response . "\n", FILE_APPEND);
            return [
                'success' => false,
                'message' => 'API returned invalid JSON',
                'raw_response' => $response
            ];
        }

        if ($result && isset($result['status']) && $result['status'] === 'success') {
            return ['success' => true, 'shortenedUrl' => $result['shortenedUrl']];
        }

        $errorMsg = $result['message'] ?? 'Unknown error';
        if (is_array($errorMsg)) {
            $errorMsg = json_encode($errorMsg);
        }
        return ['success' => false, 'message' => 'API Error: ' . $errorMsg];
}

// Get request action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        // Create new link
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($title) || empty($url)) {
            echo json_encode(['success' => false, 'message' => 'Title and URL are required']);
            exit;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
            exit;
        }
        
        // Shorten the URL
        $shortened = shortenUrl($url);
        
        if (!$shortened['success']) {
            echo json_encode($shortened);
            exit;
        }
        
        $links = loadLinks();
        
        $newLink = [
            'id' => generateId(),
            'title' => $title,
            'original_url' => $url,
            'short_url' => $shortened['shortenedUrl'],
            'category' => $category,
            'notes' => $notes,
            'clicks' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        array_unshift($links, $newLink);
        saveLinks($links);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Link created successfully',
            'link' => $newLink
        ]);
        break;
        
    case 'list':
        // Get all links
        $links = loadLinks();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';
        
        // Filter links
        if ($search) {
            $links = array_filter($links, function($link) use ($search) {
                return stripos($link['title'], $search) !== false || 
                       stripos($link['original_url'], $search) !== false;
            });
        }
        
        if ($status !== 'all') {
            $links = array_filter($links, fn($link) => $link['status'] === $status);
        }
        
        echo json_encode(['success' => true, 'links' => array_values($links)]);
        break;
        
    case 'get':
        // Get single link
        $id = $_GET['id'] ?? '';
        $links = loadLinks();
        
        $link = array_filter($links, fn($l) => $l['id'] === $id);
        $link = reset($link);
        
        if ($link) {
            echo json_encode(['success' => true, 'link' => $link]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Link not found']);
        }
        break;
        
    case 'update':
        // Update link
        $id = $_POST['id'] ?? '';
        $links = loadLinks();
        
        $updated = false;
        foreach ($links as &$link) {
            if ($link['id'] === $id) {
                $link['title'] = trim($_POST['title'] ?? $link['title']);
                $link['original_url'] = trim($_POST['url'] ?? $link['original_url']);
                $link['category'] = $_POST['category'] ?? $link['category'];
                $link['notes'] = trim($_POST['notes'] ?? $link['notes']);
                $link['status'] = $_POST['status'] ?? $link['status'];
                $link['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            saveLinks($links);
            echo json_encode(['success' => true, 'message' => 'Link updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Link not found']);
        }
        break;
        
    case 'delete':
        // Delete link
        $id = $_POST['id'] ?? '';
        $links = loadLinks();
        
        $originalCount = count($links);
        $links = array_filter($links, fn($l) => $l['id'] !== $id);
        
        if (count($links) < $originalCount) {
            saveLinks(array_values($links));
            echo json_encode(['success' => true, 'message' => 'Link deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Link not found']);
        }
        break;
        
    case 'stats':
        // Get statistics
        $links = loadLinks();
        
        $stats = [
            'total' => count($links),
            'active' => count(array_filter($links, fn($l) => $l['status'] === 'active')),
            'inactive' => count(array_filter($links, fn($l) => $l['status'] === 'inactive')),
            'clicks' => array_sum(array_column($links, 'clicks'))
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}