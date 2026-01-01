<?php
// careers.php - TechStore Careers Page
require_once 'config.php';

// 处理表单提交
$formSubmitted = false;
$formSuccess = false;
$formErrors = [];
$formData = [];
$submittedEmail = ''; // 新增变量来存储提交的邮箱

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    // 收集表单数据
    $formData = [
        'position' => $_POST['position'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'portfolio_link' => $_POST['portfolio_link'] ?? '',
        'cover_letter' => $_POST['cover_letter'] ?? '',
        'resume' => $_FILES['resume'] ?? null
    ];
    
    // 保存提交的邮箱用于显示成功消息
    $submittedEmail = $formData['email'];
    
    // 验证数据
    if (empty($formData['position'])) {
        $formErrors[] = "Please select a position";
    }
    
    if (empty($formData['full_name'])) {
        $formErrors[] = "Full name is required";
    }
    
    if (empty($formData['email'])) {
        $formErrors[] = "Email is required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = "Please enter a valid email address";
    }
    
    // 处理文件上传
    $fileUploaded = false;
    $fileError = '';
    $uploadedFileName = '';
    
    if ($formData['resume'] && $formData['resume']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = [
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        $allowedExtensions = ['.pdf', '.doc', '.docx', '.txt'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $fileName = $formData['resume']['name'];
        $fileExt = strtolower(substr($fileName, strrpos($fileName, '.')));
        
        if (!in_array($formData['resume']['type'], $allowedTypes) && !in_array($fileExt, $allowedExtensions)) {
            $formErrors[] = "Please upload a PDF, DOC, DOCX or TXT file";
        } elseif ($formData['resume']['size'] > $maxSize) {
            $formErrors[] = "File size must be less than 5MB";
        } else {
            $fileUploaded = true;
        }
    } else {
        $formErrors[] = "Please upload your resume";
        
        // 检查具体错误
        if ($formData['resume'] && $formData['resume']['error'] !== UPLOAD_ERR_OK) {
            switch ($formData['resume']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $formErrors[] = "File is too large. Maximum size is 5MB";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $formErrors[] = "File was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $formErrors[] = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $formErrors[] = "Missing temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $formErrors[] = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $formErrors[] = "File upload stopped by extension";
                    break;
            }
        }
    }
    
    // 如果没有错误，处理申请
    if (empty($formErrors)) {
        $formSubmitted = true;
        
        // 获取数据库连接
        $pdo = getDatabaseConnection();
        if ($pdo) {
            try {
                // 生成唯一的文件名
                $uploadedFileName = '';
                if ($fileUploaded) {
                    $fileExtension = pathinfo($formData['resume']['name'], PATHINFO_EXTENSION);
                    $uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
                    $uploadedFileName = $uniqueFilename;
                }
                
                // 保存申请到数据库
                $sql = "INSERT INTO job_applications (position, full_name, email, phone, portfolio_link, cover_letter, resume_filename, status) 
                        VALUES (:position, :full_name, :email, :phone, :portfolio_link, :cover_letter, :resume_filename, 'pending')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':position' => $formData['position'],
                    ':full_name' => $formData['full_name'],
                    ':email' => $formData['email'],
                    ':phone' => $formData['phone'] ?? '',
                    ':portfolio_link' => $formData['portfolio_link'] ?? '',
                    ':cover_letter' => $formData['cover_letter'] ?? '',
                    ':resume_filename' => $uploadedFileName
                ]);
                
                // 保存上传的文件
                if ($fileUploaded && $uploadedFileName) {
                    $uploadDir = __DIR__ . '/uploads/resumes/';
                    
                    // 确保上传目录存在
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $uploadPath = $uploadDir . $uploadedFileName;
                    
                    // 移动上传的文件
                    if (!move_uploaded_file($formData['resume']['tmp_name'], $uploadPath)) {
                        error_log("Failed to move uploaded file: " . $formData['resume']['name']);
                        // 即使文件保存失败，我们仍然记录申请
                    }
                }
                
                // 发送确认邮件给申请人
                $to = $formData['email'];
                $subject = "Job Application Received - TechStore";
                
                // 创建邮件内容
                $positionNames = [
                    'electronics-product-manager' => 'Electronics Product Manager',
                    'ecommerce-operations-specialist' => 'E-commerce Operations Specialist',
                    'full-stack-developer' => 'Full Stack Developer'
                ];
                
                $positionName = $positionNames[$formData['position']] ?? $formData['position'];
                
                $message = "Dear " . htmlspecialchars($formData['full_name']) . ",\r\n\r\n";
                $message .= "Thank you for your application for the position of " . $positionName . " at TechStore.\r\n\r\n";
                $message .= "We have received your application and our HR team will review it within 5 business days.\r\n\r\n";
                $message .= "Application Details:\r\n";
                $message .= "Position: " . $positionName . "\r\n";
                $message .= "Name: " . htmlspecialchars($formData['full_name']) . "\r\n";
                $message .= "Email: " . htmlspecialchars($formData['email']) . "\r\n";
                $message .= "Phone: " . (empty($formData['phone']) ? "Not provided" : htmlspecialchars($formData['phone'])) . "\r\n";
                $message .= "Application Date: " . date('F j, Y, g:i a') . "\r\n\r\n";
                $message .= "If you have any questions, please contact us at careers@techstore.com\r\n\r\n";
                $message .= "Best regards,\r\n";
                $message .= "TechStore HR Team\r\n";
                
                // 邮件头部
                $headers = "From: careers@techstore.com\r\n";
                $headers .= "Reply-To: careers@techstore.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                // 发送邮件
                if (mail($to, $subject, $message, $headers)) {
                    error_log("Confirmation email sent to: " . $to);
                } else {
                    error_log("Failed to send confirmation email to: " . $to);
                }
                
                // 同时发送通知邮件给HR
                $hrSubject = "New Job Application - " . $positionName;
                $hrMessage = "New job application received:\r\n\r\n";
                $hrMessage .= "Position: " . $positionName . "\r\n";
                $hrMessage .= "Name: " . htmlspecialchars($formData['full_name']) . "\r\n";
                $hrMessage .= "Email: " . htmlspecialchars($formData['email']) . "\r\n";
                $hrMessage .= "Phone: " . (empty($formData['phone']) ? "Not provided" : htmlspecialchars($formData['phone'])) . "\r\n";
                $hrMessage .= "Applied at: " . date('F j, Y, g:i a') . "\r\n\r\n";
                $hrMessage .= "Please review this application in the admin panel.";
                
                mail("careers@techstore.com", $hrSubject, $hrMessage);
                
            } catch (Exception $e) {
                error_log("Database error in job application: " . $e->getMessage());
                // 即使数据库出错，我们仍然显示成功消息，但记录错误
            }
        } else {
            error_log("Failed to connect to database");
        }
        
        // 模拟成功处理
        $formSuccess = true;
        
        // 重置表单数据，但保留邮箱用于显示
        $formData = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers - Join the TechStore Team</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="logo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --accent: #28a745;
            --accent-dark: #218838;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --text-light: #6c757d;
            --border: #e9ecef;
            --shadow: 0 6px 20px rgba(0,0,0,0.08);
            --shadow-light: 0 4px 12px rgba(0,0,0,0.05);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
            margin: 0;
            padding: 0;
        }

        .careers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem 3rem; /* 增加底部内边距防止内容被遮挡 */
        }

        /* Hero Section */
        .careers-hero {
            text-align: center;
            padding: 4rem 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--radius);
            margin-bottom: 3rem;
        }

        .careers-hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .careers-hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Bullet Points */
        .bullet-points {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .bullet-point {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-weight: 500;
        }

        .bullet-icon {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Section Styling */
        .section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border);
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid var(--accent);
        }

        /* Job Listings */
        .job-listings {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .job-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        .job-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .job-category {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .job-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .job-body {
            padding: 1.5rem;
        }

        .job-section {
            margin-bottom: 1.5rem;
        }

        .job-section h4 {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            margin: 0 0 0.75rem 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-section h4 i {
            color: var(--accent);
        }

        .job-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .job-list li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            color: var(--text-light);
        }

        .job-list li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }

        .job-footer {
            padding: 1.5rem;
            background: var(--bg);
            border-top: 1px solid var(--border);
            text-align: center;
        }

        /* Application Form Modal */
        .apply-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .apply-modal.active {
            display: flex;
            opacity: 1;
        }

        .apply-modal-content {
            background: white;
            border-radius: var(--radius);
            width: 100%;
            max-width: 800px; /* 增加宽度以容纳更宽的表单 */
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .apply-modal.active .apply-modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--radius) var(--radius) 0 0;
            text-align: center;
        }

        .modal-header h3 {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
        }

        .modal-subtitle {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-weight: 400;
        }

        .modal-body {
            padding: 2.5rem;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text);
            font-size: 1.1rem;
            font-family: 'Inter', sans-serif;
        }

        .form-label small {
            font-weight: 400;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .required-star {
            color: #dc3545;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: white;
        }

        .form-select {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            background: #f8f9fa;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
        }

        .form-select:focus {
            background: white;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
            line-height: 1.6;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border: 2px dashed #ced4da;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.1rem;
            color: var(--text-light);
        }

        .file-input-label:hover {
            border-color: var(--primary);
            background: rgba(0, 123, 255, 0.05);
            color: var(--primary);
        }

        .file-input-label i {
            font-size: 1.5rem;
        }

        .file-info {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: var(--text-light);
            text-align: center;
        }

        .file-name {
            margin-top: 0.75rem;
            padding: 0.5rem 1rem;
            background: #e9ecef;
            border-radius: var(--radius);
            font-size: 0.9rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-name i {
            color: var(--primary);
        }

        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 2rem 0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
        }

        .form-actions button {
            flex: 1;
        }

        .btn-apply {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-family: 'Inter', sans-serif;
        }

        .btn-apply:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-apply:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-reset {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-family: 'Inter', sans-serif;
        }

        .btn-reset:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-open-form {
            width: 100%;
        }

        /* Error/Success Messages */
        .alert {
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .error-list {
            margin: 0;
            padding-left: 1.5rem;
        }

        .error-list li {
            margin-bottom: 0.5rem;
        }

        /* Why Join Us */
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .benefit-card {
            text-align: center;
            padding: 1.5rem;
            background: var(--bg);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .benefit-card:hover {
            background: white;
            box-shadow: var(--shadow-light);
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        /* Contact Section */
        .contact-careers {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--radius);
            margin-top: 3rem;
            margin-bottom: 2rem; /* 添加底部外边距 */
        }

        .contact-careers h3 {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .contact-info {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin: 2rem auto;
            max-width: 600px;
            box-shadow: var(--shadow-light);
        }

        .contact-info p {
            margin: 0.75rem 0;
            font-size: 1.1rem;
        }

        .contact-info strong {
            color: var(--primary);
        }

        .btn-contact {
            margin-top: 1.5rem;
            padding: 0.85rem 2rem;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .careers-hero h1 {
                font-size: 2.5rem;
            }
            
            .section {
                padding: 1.5rem;
            }
            
            .job-listings {
                grid-template-columns: 1fr;
            }
            
            .bullet-points {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .apply-modal-content {
                margin: 1rem;
                max-width: 95%;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .modal-header {
                padding: 1.5rem;
            }
            
            .modal-header h3 {
                font-size: 1.5rem;
            }
            
            .contact-info {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .careers-hero {
                padding: 3rem 1rem;
            }
            
            .careers-hero h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .form-label {
                font-size: 1rem;
            }
            
            .form-control, .form-select {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Application Form Modal -->
    <div class="apply-modal" id="applyModal">
        <div class="apply-modal-content">
            <div class="modal-header">
                <button type="button" class="close-modal" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
                <h3>Submit Your Application</h3>
                <p class="modal-subtitle">Join the TechStore team and help us build the future of technology retail</p>
            </div>
            <div class="modal-body">
                <?php if ($formSubmitted && $formSuccess): ?>
                    <div class="alert alert-success">
                        <h4 style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0;">
                            <i class="fas fa-check-circle"></i> Application Submitted Successfully!
                        </h4>
                        <p style="margin: 1rem 0;">Thank you for your interest in joining TechStore. Our HR team will review your application and contact you within 5 business days.</p>
                        <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($submittedEmail); ?></strong>.</p>
                        <p><small>If you don't see the email in your inbox, please check your spam folder.</small></p>
                        <button type="button" class="btn-apply" id="closeSuccessModal" style="margin-top: 1.5rem;">Close</button>
                    </div>
                <?php else: ?>
                    <?php if (!empty($formErrors)): ?>
                        <div class="alert alert-error">
                            <h4 style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0;">
                                <i class="fas fa-exclamation-triangle"></i> Please fix the following errors:
                            </h4>
                            <ul class="error-list">
                                <?php foreach ($formErrors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="applicationForm">
                        <input type="hidden" name="apply_job" value="1">
                        
                        <div class="form-group">
                            <label for="position" class="form-label">
                                Position Applied For <span class="required-star">*</span>
                            </label>
                            <select id="position" name="position" class="form-select" required>
                                <option value="">Select Position</option>
                                <option value="electronics-product-manager" <?php echo ($formData['position'] ?? '') === 'electronics-product-manager' ? 'selected' : ''; ?>>Electronics Product Manager</option>
                                <option value="ecommerce-operations-specialist" <?php echo ($formData['position'] ?? '') === 'ecommerce-operations-specialist' ? 'selected' : ''; ?>>E-commerce Operations Specialist</option>
                                <option value="full-stack-developer" <?php echo ($formData['position'] ?? '') === 'full-stack-developer' ? 'selected' : ''; ?>>Full Stack Developer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name" class="form-label">
                                Full Name <span class="required-star">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>" 
                                   placeholder="Enter your full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">
                                Email <span class="required-star">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                Phone <small>(Optional)</small>
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
                                   placeholder="Enter your phone number">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Upload Resume <span class="required-star">*</span>
                            </label>
                            <div class="file-input-wrapper">
                                <label class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span id="fileLabel">Choose File</span>
                                </label>
                                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx,.txt" required>
                            </div>
                            <div class="file-info">
                                No file selected<br>
                                Supported formats: PDF, DOC, DOCX, TXT - Maximum 5MB
                            </div>
                            <div class="file-name" id="fileName"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="portfolio_link" class="form-label">
                                Cover Letter / Portfolio Link <small>(Optional)</small>
                            </label>
                            <textarea id="portfolio_link" name="portfolio_link" class="form-control" 
                                      placeholder="Share your background, expertise, and outstanding projects"><?php echo htmlspecialchars($formData['portfolio_link'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="cover_letter" class="form-label">
                                Additional Information <small>(Optional)</small>
                            </label>
                            <textarea id="cover_letter" name="cover_letter" class="form-control" 
                                      placeholder="Any additional information you'd like to share..."><?php echo htmlspecialchars($formData['cover_letter'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-apply">
                                <i class="fas fa-paper-plane"></i> SUBMIT APPLICATION
                            </button>
                            <button type="button" class="btn-reset" id="resetFormBtn">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                        </div>
                        
                        <p style="text-align: center; color: var(--text-light); font-size: 0.9rem; margin-top: 2rem;">
                            By submitting, you agree to our <a href="privacy.php" style="color: var(--primary); text-decoration: none;">Privacy Policy</a> and consent to our HR team contacting you.
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="careers-container">
        <!-- Hero Section -->
        <section class="careers-hero">
            <h1>Join the TechStore Team</h1>
            <p>Build the future of premium electronics retail with us. We're looking for talented individuals who are passionate about technology and innovation.</p>
            
            <div class="bullet-points">
                <div class="bullet-point">
                    <div class="bullet-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <span>Paradise for tech enthusiasts</span>
                </div>
                <div class="bullet-point">
                    <div class="bullet-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>Teamwork and innovation</span>
                </div>
                <div class="bullet-point">
                    <div class="bullet-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span>Rapid growth and development</span>
                </div>
            </div>
        </section>

        <!-- Company Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Satisfied Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100+</div>
                <div class="stat-label">Product Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">95%</div>
                <div class="stat-label">Customer Satisfaction</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">10+</div>
                <div class="stat-label">Countries Served</div>
            </div>
        </div>

        <!-- Current Openings -->
        <section class="section">
            <h2 class="section-title">We are Hiring</h2>
            <p>Check out our current openings and find a position that matches your skills and passion.</p>
            
            <div class="job-listings">
                <!-- Job 1 -->
                <div class="job-card">
                    <div class="job-header">
                        <span class="job-category">Product</span>
                        <h3 class="job-title">Electronics Product Manager</h3>
                    </div>
                    <div class="job-body">
                        <p>Responsible for the planning and management of electronics product lines, including market research, product design, development coordination and marketing strategy.</p>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-requirements"></i> Job Requirements</h4>
                            <ul class="job-list">
                                <li>3+ years of product management experience in electronics</li>
                                <li>Deep understanding of consumer electronics market</li>
                                <li>Excellent communication and leadership skills</li>
                                <li>Bachelor's degree in Business or Engineering</li>
                                <li>Tech enthusiast with passion for innovation</li>
                            </ul>
                        </div>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-tasks"></i> Job Responsibilities</h4>
                            <ul class="job-list">
                                <li>Conduct market research and competitive analysis</li>
                                <li>Develop comprehensive product roadmaps</li>
                                <li>Coordinate with engineering and design teams</li>
                                <li>Track product performance and user feedback</li>
                                <li>Manage product lifecycle from concept to launch</li>
                            </ul>
                        </div>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-gift"></i> Benefits</h4>
                            <ul class="job-list">
                                <li>Competitive salary with performance bonuses</li>
                                <li>Comprehensive health insurance package</li>
                                <li>Flexible working hours and remote options</li>
                                <li>Latest tech equipment and gadgets</li>
                                <li>Professional development budget</li>
                            </ul>
                        </div>
                    </div>
                    <div class="job-footer">
                        <button type="button" class="btn-apply btn-open-form" data-position="electronics-product-manager">
                            Apply Now
                        </button>
                    </div>
                </div>
                
                <!-- Job 2 -->
                <div class="job-card">
                    <div class="job-header">
                        <span class="job-category">E-commerce</span>
                        <h3 class="job-title">E-commerce Operations Specialist</h3>
                    </div>
                    <div class="job-body">
                        <p>Responsible for daily operations of TechStore e-commerce platform, including product listing, promotional campaigns, user engagement and data analytics.</p>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-requirements"></i> Job Requirements</h4>
                            <ul class="job-list">
                                <li>2+ years of e-commerce operations experience</li>
                                <li>Familiar with major e-commerce platforms and tools</li>
                                <li>Proficient in Excel/Google Sheets for data analysis</li>
                                <li>Strong attention to detail and organizational skills</li>
                                <li>Knowledge of SEO and digital marketing principles</li>
                            </ul>
                        </div>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-tasks"></i> Job Responsibilities</h4>
                            <ul class="job-list">
                                <li>Manage product listings and inventory updates</li>
                                <li>Plan and execute promotional campaigns</li>
                                <li>Monitor and analyze sales performance data</li>
                                <li>Optimize product pages for conversion</li>
                                <li>Coordinate with customer service team</li>
                            </ul>
                        </div>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-gift"></i> Benefits</h4>
                            <ul class="job-list">
                                <li>Competitive compensation package</li>
                                <li>Health and dental insurance</li>
                                <li>Employee discount on all products</li>
                                <li>Quarterly performance bonuses</li>
                                <li>Opportunities for career advancement</li>
                            </ul>
                        </div>
                    </div>
                    <div class="job-footer">
                        <button type="button" class="btn-apply btn-open-form" data-position="ecommerce-operations-specialist">
                            Apply Now
                        </button>
                    </div>
                </div>
                
                <!-- Job 3 -->
                <div class="job-card">
                    <div class="job-header">
                        <span class="job-category">Technology</span>
                        <h3 class="job-title">Full Stack Developer</h3>
                    </div>
                    <div class="job-body">
                        <p>Join our tech team to build and maintain TechStore's e-commerce platform, implementing new features and ensuring optimal performance.</p>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-requirements"></i> Job Requirements</h4>
                            <ul class="job-list">
                                <li>3+ years of full stack development experience</li>
                                <li>Proficient in PHP, JavaScript, HTML/CSS</li>
                                <li>Experience with MySQL and database design</li>
                                <li>Knowledge of modern web development frameworks</li>
                                <li>Understanding of e-commerce systems</li>
                            </ul>
                        </div>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-tasks"></i> Job Responsibilities</h4>
                            <ul class="job-list">
                                <li>Develop and maintain e-commerce platform features</li>
                                <li>Optimize website performance and user experience</li>
                                <li>Implement secure payment processing systems</li>
                                <li>Collaborate with UX/UI designers</li>
                                <li>Troubleshoot and debug technical issues</li>
                            </ul>
                        </div>
                        
                        <div class="job-section">
                            <h4><i class="fas fa-gift"></i> Benefits</h4>
                            <ul class="job-list">
                                <li>Above-market salary for tech talent</li>
                                <li>Stock options program</li>
                                <li>Latest development hardware provided</li>
                                <li>Flexible schedule and remote work options</li>
                                <li>Conference and training budget</li>
                            </ul>
                        </div>
                    </div>
                    <div class="job-footer">
                        <button type="button" class="btn-apply btn-open-form" data-position="full-stack-developer">
                            Apply Now
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Join TechStore -->
        <section class="section">
            <h2 class="section-title">Why Join TechStore?</h2>
            <p>At TechStore, we believe our people are our greatest asset. Here's what makes us different:</p>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h4>Innovation Culture</h4>
                    <p>We encourage creative thinking and provide resources to turn ideas into reality.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4>Collaborative Environment</h4>
                    <p>Work with talented colleagues who support and challenge each other to excel.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h4>Growth Opportunities</h4>
                    <p>Continuous learning with training programs and clear career advancement paths.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Work-Life Balance</h4>
                    <p>Flexible schedules and generous time-off policies to maintain healthy balance.</p>
                </div>
            </div>
        </section>

        <!-- Application Process -->
        <section class="section">
            <h2 class="section-title">Our Hiring Process</h2>
            <p>We've designed our hiring process to be transparent, efficient, and respectful of your time.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 1.5rem; background: var(--bg); border-radius: var(--radius);">
                    <div style="width: 50px; height: 50px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-weight: bold;">1</div>
                    <h4 style="margin: 0 0 0.5rem 0;">Application Review</h4>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">We review all applications within 5 business days</p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg); border-radius: var(--radius);">
                    <div style="width: 50px; height: 50px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-weight: bold;">2</div>
                    <h4 style="margin: 0 0 0.5rem 0;">Initial Interview</h4>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">30-minute video call with HR or hiring manager</p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg); border-radius: var(--radius);">
                    <div style="width: 50px; height: 50px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-weight: bold;">3</div>
                    <h4 style="margin: 0 0 0.5rem 0;">Technical Assessment</h4>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">Role-specific task or discussion with team</p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg); border-radius: var(--radius);">
                    <div style="width: 50px; height: 50px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-weight: bold;">4</div>
                    <h4 style="margin: 0 0 0.5rem 0;">Final Interview</h4>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">Meeting with department head and culture fit discussion</p>
                </div>
            </div>
        </section>

        <!-- Contact for Questions -->
        <section class="contact-careers">
            <h3>Questions About Our Openings?</h3>
            <p>If you have any questions about our positions or the application process, feel free to reach out to our HR team.</p>
            
            <div class="contact-info">
                <p><strong>Email:</strong> careers@techstore.com</p>
                <p><strong>Phone:</strong> +1 (555) 123-4567 (Ext. 100)</p>
                <p><strong>Office Hours:</strong> Monday - Friday, 9:00 AM - 6:00 PM EST</p>
            </div>
            
            <!-- 修改这里的按钮，让它滚动到页面顶部并打开模态窗口 -->
            <button type="button" class="btn-apply btn-contact" id="quickApplyBtn">
                <i class="fas fa-paper-plane"></i> Quick Apply Now
            </button>
        </section>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const applyModal = document.getElementById('applyModal');
            const closeModal = document.getElementById('closeModal');
            const closeSuccessModal = document.getElementById('closeSuccessModal');
            const applyButtons = document.querySelectorAll('.btn-open-form');
            const quickApplyBtn = document.getElementById('quickApplyBtn');
            const applicationForm = document.getElementById('applicationForm');
            const resetFormBtn = document.getElementById('resetFormBtn');
            const fileInput = document.getElementById('resume');
            const fileName = document.getElementById('fileName');
            const fileLabel = document.getElementById('fileLabel');
            const positionSelect = document.getElementById('position');

            // Open modal when Apply Now button is clicked
            applyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const position = this.getAttribute('data-position');
                    
                    // 设置选中的职位
                    if (positionSelect) {
                        positionSelect.value = position;
                    }
                    
                    // 重置表单如果有之前的错误
                    if (applicationForm) {
                        applicationForm.reset();
                        fileName.textContent = '';
                        fileLabel.textContent = 'Choose File';
                    }
                    
                    openApplicationModal();
                });
            });

            // Quick Apply button
            if (quickApplyBtn) {
                quickApplyBtn.addEventListener('click', function() {
                    // 滚动到顶部
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    
                    // 打开模态窗口
                    setTimeout(() => {
                        openApplicationModal();
                    }, 500);
                });
            }

            // Open modal function
            function openApplicationModal() {
                applyModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // 滚动到模态窗口
                setTimeout(() => {
                    applyModal.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            }

            // Close modal
            function closeApplicationModal() {
                applyModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            closeModal.addEventListener('click', closeApplicationModal);
            
            if (closeSuccessModal) {
                closeSuccessModal.addEventListener('click', closeApplicationModal);
            }

            // Close modal when clicking outside
            applyModal.addEventListener('click', function(e) {
                if (e.target === applyModal) {
                    closeApplicationModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && applyModal.classList.contains('active')) {
                    closeApplicationModal();
                }
            });

            // Reset form button
            if (resetFormBtn) {
                resetFormBtn.addEventListener('click', function() {
                    if (applicationForm) {
                        applicationForm.reset();
                        fileName.textContent = '';
                        fileLabel.textContent = 'Choose File';
                        
                        // 显示重置成功的消息
                        const resetMsg = document.createElement('div');
                        resetMsg.className = 'alert alert-success';
                        resetMsg.innerHTML = '<p><i class="fas fa-check"></i> Form has been reset</p>';
                        resetMsg.style.marginTop = '1rem';
                        
                        // 移除之前的消息
                        const existingAlert = applicationForm.querySelector('.alert');
                        if (existingAlert) {
                            existingAlert.remove();
                        }
                        
                        applicationForm.prepend(resetMsg);
                        
                        // 3秒后移除消息
                        setTimeout(() => {
                            resetMsg.remove();
                        }, 3000);
                    }
                });
            }

            // File input display
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                        fileName.innerHTML = `<i class="fas fa-file"></i> Selected: ${file.name} (${fileSizeMB} MB)`;
                        fileLabel.textContent = 'Change File';
                    } else {
                        fileName.textContent = '';
                        fileLabel.textContent = 'Choose File';
                    }
                });
            }

            // Form validation before submit
            if (applicationForm) {
                applicationForm.addEventListener('submit', function(e) {
                    const requiredFields = this.querySelectorAll('[required]');
                    let isValid = true;
                    
                    // 重置错误样式
                    this.querySelectorAll('.form-control, .form-select').forEach(field => {
                        field.style.borderColor = '';
                    });
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = '#dc3545';
                            
                            // 为文件输入添加错误样式
                            if (field.type === 'file') {
                                field.parentElement.querySelector('.file-input-label').style.borderColor = '#dc3545';
                            }
                        } else {
                            field.style.borderColor = '';
                            
                            // 移除文件输入的错误样式
                            if (field.type === 'file') {
                                field.parentElement.querySelector('.file-input-label').style.borderColor = '';
                            }
                        }
                        
                        // Email validation
                        if (field.type === 'email' && field.value.trim()) {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(field.value)) {
                                isValid = false;
                                field.style.borderColor = '#dc3545';
                            }
                        }
                    });
                    
                    // 文件验证
                    if (fileInput && fileInput.files.length === 0) {
                        isValid = false;
                        fileInput.parentElement.querySelector('.file-input-label').style.borderColor = '#dc3545';
                    } else if (fileInput && fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        const allowedExtensions = ['.pdf', '.doc', '.docx', '.txt'];
                        const maxSize = 5 * 1024 * 1024;
                        
                        const fileName = file.name.toLowerCase();
                        const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                        
                        if (!hasValidExtension) {
                            isValid = false;
                            alert('Please upload only PDF, DOC, DOCX or TXT files.');
                            e.preventDefault();
                            return false;
                        }
                        
                        if (file.size > maxSize) {
                            isValid = false;
                            alert('File size must be less than 5MB.');
                            e.preventDefault();
                            return false;
                        }
                        
                        fileInput.parentElement.querySelector('.file-input-label').style.borderColor = '';
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        
                        // 显示错误消息
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'alert alert-error';
                        errorMsg.innerHTML = '<p><i class="fas fa-exclamation-circle"></i> Please fill in all required fields marked with *</p>';
                        
                        // 移除之前的错误消息
                        const existingAlert = this.querySelector('.alert');
                        if (existingAlert && existingAlert.classList.contains('alert-error')) {
                            existingAlert.remove();
                        }
                        
                        this.prepend(errorMsg);
                        
                        // 滚动到错误消息
                        errorMsg.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SUBMITTING...';
                        submitBtn.disabled = true;
                    }
                });
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if(targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if(targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Job card hover effects enhancement
            const jobCards = document.querySelectorAll('.job-card');
            jobCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.zIndex = '';
                });
            });

            // Auto-open modal if there were form errors
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job']) && !empty($formErrors)): ?>
                setTimeout(() => {
                    openApplicationModal();
                }, 300);
            <?php endif; ?>

            // Animation for stats counter (optional enhancement)
            const statNumbers = document.querySelectorAll('.stat-number');
            const observerOptions = {
                threshold: 0.5
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if(entry.isIntersecting) {
                        const stat = entry.target;
                        const text = stat.textContent;
                        const targetValue = parseInt(text.replace(/[^0-9]/g, ''));
                        const suffix = text.replace(/[0-9]/g, '');
                        const increment = targetValue / 50;
                        let currentValue = 0;
                        
                        const timer = setInterval(() => {
                            currentValue += increment;
                            if(currentValue >= targetValue) {
                                currentValue = targetValue;
                                clearInterval(timer);
                            }
                            stat.textContent = Math.floor(currentValue) + suffix;
                        }, 30);
                        
                        observer.unobserve(stat);
                    }
                });
            }, observerOptions);

            statNumbers.forEach(stat => observer.observe(stat));
        });
    </script>
</body>
</html>