<?php

$dir = __DIR__ . '/app/Models/';

$models = [
    'Program.php' => "protected \$fillable = ['name', 'description'];",
    'Level.php' => "protected \$fillable = ['program_id', 'name', 'description'];",
    'CourseClass.php' => "protected \$fillable = ['level_id', 'name', 'start_date', 'end_date'];",
    'CourseMaterial.php' => "protected \$fillable = ['course_class_id', 'title', 'type', 'file_path'];",
    'ClassSession.php' => "protected \$fillable = ['course_class_id', 'coach_id', 'start_time', 'end_time', 'status'];",
    'SessionReport.php' => "protected \$fillable = ['class_session_id', 'progress', 'observations', 'recommendations'];",
    'Attendance.php' => "protected \$fillable = ['class_session_id', 'student_id', 'is_present'];",
    'Assignment.php' => "protected \$fillable = ['course_class_id', 'coach_id', 'title', 'description', 'type', 'due_date'];",
    'Submission.php' => "protected \$fillable = ['assignment_id', 'student_id', 'content_text', 'file_path', 'submitted_at'];",
    'Grade.php' => "protected \$fillable = ['submission_id', 'coach_id', 'score', 'feedback'];",
    'PaymentRule.php' => "protected \$fillable = ['coach_id', 'rate_per_session'];",
    'Payment.php' => "protected \$fillable = ['coach_id', 'month', 'year', 'total_sessions', 'total_amount', 'status', 'validated_by'];",
];

foreach ($models as $file => $fillable) {
    $path = $dir . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, '$fillable') === false) {
            $content = str_replace("use HasFactory;", "use HasFactory;\n\n    " . $fillable, $content);
            file_put_contents($path, $content);
            echo "Updated $file\n";
        }
    }
}
