<?php

$dir = __DIR__ . '/database/migrations/';
$files = scandir($dir);

$schemas = [
    'create_programs_table' => "\$table->string('name');\n            \$table->text('description')->nullable();",
    'create_levels_table' => "\$table->foreignId('program_id')->constrained()->cascadeOnDelete();\n            \$table->string('name');\n            \$table->text('description')->nullable();",
    'create_course_classes_table' => "\$table->foreignId('level_id')->constrained()->cascadeOnDelete();\n            \$table->string('name');\n            \$table->date('start_date')->nullable();\n            \$table->date('end_date')->nullable();",
    'create_course_materials_table' => "\$table->foreignId('course_class_id')->constrained()->cascadeOnDelete();\n            \$table->string('title');\n            \$table->string('type');\n            \$table->string('file_path')->nullable();",
    'create_class_sessions_table' => "\$table->foreignId('course_class_id')->constrained()->cascadeOnDelete();\n            \$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();\n            \$table->dateTime('start_time');\n            \$table->dateTime('end_time');\n            \$table->string('status')->default('scheduled');",
    'create_session_reports_table' => "\$table->foreignId('class_session_id')->constrained()->cascadeOnDelete();\n            \$table->text('progress')->nullable();\n            \$table->text('observations')->nullable();\n            \$table->text('recommendations')->nullable();",
    'create_attendances_table' => "\$table->foreignId('class_session_id')->constrained()->cascadeOnDelete();\n            \$table->foreignId('student_id')->constrained('users')->cascadeOnDelete();\n            \$table->boolean('is_present')->default(false);",
    'create_assignments_table' => "\$table->foreignId('course_class_id')->constrained()->cascadeOnDelete();\n            \$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();\n            \$table->string('title');\n            \$table->text('description')->nullable();\n            \$table->string('type');\n            \$table->dateTime('due_date')->nullable();",
    'create_submissions_table' => "\$table->foreignId('assignment_id')->constrained()->cascadeOnDelete();\n            \$table->foreignId('student_id')->constrained('users')->cascadeOnDelete();\n            \$table->text('content_text')->nullable();\n            \$table->string('file_path')->nullable();\n            \$table->timestamp('submitted_at')->nullable();",
    'create_grades_table' => "\$table->foreignId('submission_id')->constrained()->cascadeOnDelete();\n            \$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();\n            \$table->decimal('score', 5, 2)->nullable();\n            \$table->text('feedback')->nullable();",
    'create_payment_rules_table' => "\$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();\n            \$table->decimal('rate_per_session', 8, 2);",
    'create_payments_table' => "\$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();\n            \$table->integer('month');\n            \$table->integer('year');\n            \$table->integer('total_sessions');\n            \$table->decimal('total_amount', 10, 2);\n            \$table->string('status')->default('pending');\n            \$table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();",
    'create_course_class_user_table' => "\$table->foreignId('course_class_id')->constrained()->cascadeOnDelete();\n            \$table->foreignId('user_id')->constrained()->cascadeOnDelete();\n            \$table->string('role');",
];

foreach ($files as $file) {
    if (strpos($file, '.php') === false) continue;
    
    foreach ($schemas as $key => $schema) {
        if (strpos($file, $key) !== false) {
            $path = $dir . $file;
            $content = file_get_contents($path);
            
            // Inject schema before $table->timestamps();
            $content = str_replace("\$table->timestamps();", $schema . "\n            \$table->timestamps();", $content);
            
            file_put_contents($path, $content);
            echo "Updated $file\n";
        }
    }
}
