<?php
// 定义CSV文件路径
define('CSV_FILE', 'ti.csv');
define('SELECTED_FILE', 'selected.json');

// 检查是否从index.php提交而来
if (!isset($_POST['type']) || empty($_POST['type'])) {
    header('Location: index.php');
    exit;
}

$selected_type = $_POST['type'];

// 读取已选题目记录
$selected_data = [];
if (file_exists(SELECTED_FILE)) {
    $selected_content = file_get_contents(SELECTED_FILE);
    if ($selected_content) {
        $selected_data = json_decode($selected_content, true);
    }
}

// 读取CSV文件，获取指定类型的题目
$type_questions = [];
$all_questions = [];

if (file_exists(CSV_FILE)) {
    $handle = fopen(CSV_FILE, 'r');
    if ($handle !== FALSE) {
        // 跳过表头
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 3) {
                $type = trim($data[0]);
                $question = trim($data[1]);
                $answer = trim($data[2]);
                
                if (!empty($type) && !empty($question)) {
                    if ($type === $selected_type) {
                        $type_questions[] = [
                            'question' => $question,
                            'answer' => $answer
                        ];
                    }
                    
                    // 同时收集所有题目用于统计
                    $all_questions[] = [
                        'type' => $type,
                        'question' => $question,
                        'answer' => $answer
                    ];
                }
            }
        }
        fclose($handle);
    }
}

// 如果没有找到对应类型的题目，跳转回首页
if (empty($type_questions)) {
    header('Location: index.php');
    exit;
}

// 筛选出24小时内未选过的题目
$available_questions = [];
foreach ($type_questions as $question_item) {
    $question_hash = md5($selected_type . $question_item['question']);
    $is_selected = false;
    
    if (isset($selected_data[$question_hash])) {
        // 检查是否在24小时内
        if (time() - $selected_data[$question_hash]['time'] < 86400) {
            $is_selected = true;
        }
    }
    
    if (!$is_selected) {
        $available_questions[] = $question_item;
    }
}

// 如果没有可用的题目（即所有题目都在24小时内被选过），解除限制
if (empty($available_questions)) {
    $available_questions = $type_questions;
}

// 随机选择一道题
$selected_question = $available_questions[array_rand($available_questions)];

// 记录已选的题目
$question_hash = md5($selected_type . $selected_question['question']);
$selected_data[$question_hash] = [
    'time' => time(),
    'type' => $selected_type,
    'question' => $selected_question['question']
];

// 保存已选题目记录
file_put_contents(SELECTED_FILE, json_encode($selected_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 计算统计数据
$total_questions = count($all_questions);
$selected_in_24h = 0;
foreach ($selected_data as $item) {
    if (time() - $item['time'] < 86400) {
        $selected_in_24h++;
    }
}

// 生成隐藏备注信息
$note_content = "当前题型：{$selected_type}，题目总数：" . count($type_questions);
$note_content .= "，24小时内已选题数：{$selected_in_24h}，总题数：{$total_questions}。";
$note_content .= " 要清除记录，请访问 index.php?r=0";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>题目查看 - 马年新春出题系统</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- 马年装饰元素 -->
    <div class="horse-decoration horse-1"></div>
    <div class="horse-decoration horse-2"></div>
    
    <div class="container">
        <div class="question-container">
            <!-- 上部区域 - 题干 -->
            <div class="question-top">
                <div class="question-type">
                    <?php echo htmlspecialchars($selected_type); ?>
                </div>
                <div class="question-content">
                    <?php echo nl2br(htmlspecialchars($selected_question['question'])); ?>
                </div>
            </div>
            
            <!-- 中部区域 - 答案（初始隐藏） -->
            <div class="question-middle" id="answer-section">
                <div class="answer-title">
                    <i class="fas fa-key"></i> 参考答案
                </div>
                <div class="answer-content">
                    <?php echo nl2br(htmlspecialchars($selected_question['answer'])); ?>
                </div>
            </div>
            
            <!-- 下部区域 - 按钮 -->
            <div class="question-bottom">
                <button class="action-btn answer-btn" id="show-answer">
                    <i class="fas fa-lightbulb"></i> 参考答案
                </button>
                <a href="index.php" class="action-btn back-btn">
                    <i class="fas fa-arrow-left"></i> 返回首页
                </a>
            </div>
        </div>
        
        <div class="instructions" style="margin-top: 30px;">
            <h2><i class="fas fa-chart-bar"></i> 统计信息</h2>
            <p>当前题型：<strong><?php echo htmlspecialchars($selected_type); ?></strong></p>
            <p>该题型题目总数：<strong><?php echo count($type_questions); ?></strong> 道</p>
            <p>24小时内已选题目：<strong><?php echo $selected_in_24h; ?></strong> 道（所有题型）</p>
            <p>系统总题目数：<strong><?php echo $total_questions; ?></strong> 道</p>
        </div>
    </div>
    
    <!-- 隐藏的备注信息，只在查看源代码时可见 -->
    <div class="hidden-note">
        <?php echo htmlspecialchars($note_content); ?>
    </div>
    
    <script>
        // 显示答案按钮点击事件
        document.getElementById('show-answer').addEventListener('click', function() {
            const answerSection = document.getElementById('answer-section');
            answerSection.classList.add('show');
            
            // 更改按钮文本和状态
            this.innerHTML = '<i class="fas fa-check"></i> 答案已显示';
            this.disabled = true;
            this.style.opacity = '0.7';
            this.style.cursor = 'default';
        });
        
        // 页面加载后添加一些动画效果
        document.addEventListener('DOMContentLoaded', function() {
            const questionContainer = document.querySelector('.question-container');
            questionContainer.style.opacity = '0';
            questionContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                questionContainer.style.transition = 'opacity 0.5s, transform 0.5s';
                questionContainer.style.opacity = '1';
                questionContainer.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
