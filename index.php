<?php
// 定义CSV文件路径
define('CSV_FILE', 'ti.csv');
define('SELECTED_FILE', 'selected.json');

// 处理清除记录请求
if (isset($_GET['r']) && $_GET['r'] == '0') {
    if (file_exists(SELECTED_FILE)) {
        unlink(SELECTED_FILE);
    }
    // 重定向，移除查询参数
    header('Location: index.php');
    exit;
}

// 初始化已选题目记录
$selected_data = [];
if (file_exists(SELECTED_FILE)) {
    $selected_content = file_get_contents(SELECTED_FILE);
    if ($selected_content) {
        $selected_data = json_decode($selected_content, true);
    }
}

// 读取CSV文件
$question_types = [];
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
                    if (!isset($question_types[$type])) {
                        $question_types[$type] = [
                            'count' => 0,
                            'questions' => []
                        ];
                    }
                    
                    $question_types[$type]['count']++;
                    $question_types[$type]['questions'][] = [
                        'question' => $question,
                        'answer' => $answer
                    ];
                }
            }
        }
        fclose($handle);
    }
}

// 计算题型总数
$type_count = count($question_types);

// 生成隐藏备注信息
$note_content = "系统会记住24小时内已选择的题目，避免重复出题。";
if (!empty($selected_data)) {
    $selected_count = 0;
    foreach ($selected_data as $item) {
        if (time() - $item['time'] < 86400) { // 24小时
            $selected_count++;
        }
    }
    $note_content .= " 当前已有 {$selected_count} 道题在24小时内被选过。";
}
$note_content .= " 要清除记录，请访问 index.php?r=0";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>马年新春出题系统</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- 马年装饰元素 -->
    <div class="horse-decoration horse-1"></div>
    <div class="horse-decoration horse-2"></div>
    <div class="horse-decoration horse-3"></div>
    <div class="horse-decoration horse-4"></div>
    
    <div class="container">
        <div class="header">
            <h1>马年新春出题系统（Ver0.3）</h1>
            <p class="subtitle">门盈四海笑谈砼，佳景初临祥瑞融</p>
	    <p class="subtitle">最是马年顺遂日，骏业腾飞万域中</p>
      </div>
        
        <?php if (empty($question_types)): ?>
            <div class="instructions">
                <h2><i class="fas fa-info-circle"></i> 提示</h2>
                <p>未找到题目数据，请确保 ti.csv 文件存在且格式正确。</p>
                <p>CSV文件格式应为：第一列题型，第二列题干，第三列答案。</p>
            </div>
        <?php else: ?>
            <div class="buttons-container">
                <?php foreach ($question_types as $type_name => $type_data): ?>
                    <?php
                    // 计算该题型下24小时内未选过的题目数量
                    $available_count = 0;
                    foreach ($type_data['questions'] as $question_item) {
                        $question_hash = md5($type_name . $question_item['question']);
                        $is_selected = false;
                        
                        if (isset($selected_data[$question_hash])) {
                            // 检查是否在24小时内
                            if (time() - $selected_data[$question_hash]['time'] < 86400) {
                                $is_selected = true;
                            }
                        }
                        
                        if (!$is_selected) {
                            $available_count++;
                        }
                    }
                    
                    // 如果没有可用的题目，解除限制
                    $disable_btn = ($available_count == 0) ? true : false;
                    ?>
                    <form action="view.php" method="POST" class="type-form">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_name); ?>">
                        <button type="submit" class="type-btn" <?php echo $disable_btn ? 'disabled' : ''; ?>>
                            <span class="btn-name"><?php echo htmlspecialchars($type_name); ?></span>
                            <span class="btn-desc"><?php echo $type_data['count']; ?> 道题</span>
                            <?php if ($available_count < $type_data['count']): ?>
                                <span class="btn-desc" style="font-size:0.8rem; margin-top:5px;">
                                    <?php echo $available_count; ?> 道未选
                                </span>
                            <?php endif; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            
            <div class="instructions">
                <h2><i class="fas fa-info-circle"></i> 使用说明</h2>
                <p>1. 点击上方按钮选择题目类型，系统将随机从该类型中抽取一道题目</p>
                <p>2. 点击"参考答案"按钮可以查看题目答案</p>
	        <p>3. 如有疑问，<a href="mailto:?subject=出题系统问询&body=您好，%0A出题系统需要怎么样的运行环境？">点击发邮件给作者: zhszhnt </a> </p>
                <p>4. 项目GitHub地址：<a href="https://github.com/zhszhnt/PartQuiS/">https://github.com/zhszhnt/PartQuiS/</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 隐藏的备注信息，只在查看源代码时可见 -->
    <div class="hidden-note">
        <?php echo htmlspecialchars($note_content); ?>
	<p>4. 如需清除已选记录，请访问 <a href="index.php?r=0">index.php?r=0</a></p>
	<p>5. 当某类型所有题目都在24小时内被选过时，系统将解除限制重新选题</p>
        <p>6. 系统会记录24小时内已选过的题目，避免重复出题</p>
        <p>7. 作者:jonwang, Ver0.3</p>
    </div>
</body>
</html>
