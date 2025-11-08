<?php
// question_bank_list.php - Questions List Component
?>
<?php if ($selectedGrade && $selectedSubjectId): ?>
    <div class="accordion" id="topicsAccordion">
        <?php
        $topicCounter = 0;
        $globalIndex = 0;
        foreach ($questionsData as $topicIndex => $topicData):
            $topic = $topicData['topic'] ?? 'Chủ đề không xác định';
            $lessons = $topicData['questions'] ?? [];
            $totalQuestionsInTopic = count($lessons);
            $topicCounter++;
        ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?php echo $topicCounter; ?>">
                    <button class="accordion-button <?php echo $topicCounter > 1 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $topicCounter; ?>" aria-expanded="<?php echo $topicCounter === 1 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $topicCounter; ?>">
                        📚 <?php echo htmlspecialchars($topic); ?> (<?php echo $totalQuestionsInTopic; ?> câu hỏi)
                    </button>
                </h2>
                <div id="collapse<?php echo $topicCounter; ?>" class="accordion-collapse collapse <?php echo $topicCounter === 1 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $topicCounter; ?>" data-bs-parent="#topicsAccordion">
                    <div class="accordion-body">
                        <?php
                        $lessonGroups = [];
                        foreach ($lessons as $lessonIndex => $q) {
                            $lesson = $topicData['lesson'] ?? 'Bài học không xác định';
                            if (!isset($lessonGroups[$lesson])) {
                                $lessonGroups[$lesson] = [];
                            }
                            $lessonGroups[$lesson][] = ['data' => $q, 'index' => $lessonIndex, 'globalIndex' => $globalIndex++];
                        }
                        foreach ($lessonGroups as $lesson => $lessonQuestions):
                        ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">📖 <?php echo htmlspecialchars($lesson); ?> (<?php echo count($lessonQuestions); ?> câu hỏi)</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Câu hỏi</th>
                                                <th>Đáp án</th>
                                                <th>Loại</th>
                                                <th>Mức độ</th>
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lessonQuestions as $item): ?>
                                                <?php $q = $item['data']; $flatIndex = $item['globalIndex']; ?>
                                                <tr onclick="if (!event.target.closest('.delete-question')) { const modal = new bootstrap.Modal(document.getElementById('questionModal<?php echo $flatIndex; ?>')); modal.show(); }" style="cursor:pointer;">
                                                    <td><?php echo $flatIndex + 1; ?></td>
                                                    <td><?php echo strip_tags($q['question'], '<img>'); ?></td>
                                                    <td><?php echo renderCorrect($q['correct'], $q['options']); ?></td>
                                                    <td><?php echo $q['type'] === 'single' ? 'Trắc nghiệm' : 'Trắc nghiệm nhiều đáp án'; ?></td>
                                                    <td>
                                                        <?php
                                                        $levelLabels = ['NB' => 'Nhận biết', 'TH' => 'Thông hiểu', 'VD' => 'Vận dụng', 'VDC' => 'Vận dụng cao'];
                                                        echo $levelLabels[$q['level']] ?? htmlspecialchars($q['level']);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-danger btn-sm delete-question" data-topic-index="<?php echo $topicIndex; ?>" data-index="<?php echo $item['index']; ?>" title="Xóa câu hỏi">
                                                            🗑️ Xóa
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Modal -->
                                                <div class="modal fade" id="questionModal<?php echo $flatIndex; ?>" tabindex="-1" aria-labelledby="questionModalLabel<?php echo $flatIndex; ?>" aria-hidden="true">
                                                  <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                      <div class="modal-header">
                                                        <h5 class="modal-title" id="questionModalLabel<?php echo $flatIndex; ?>">Chi tiết câu hỏi #<?php echo $flatIndex + 1; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                      </div>
                                                      <div class="modal-body">
                                                        <p><strong>Câu hỏi:</strong> <?php echo strip_tags($q['question'], '<img>'); ?></p>
                                                        <p><strong>Loại câu hỏi:</strong> <?php echo $q['type'] === 'single' ? 'Trắc nghiệm' : 'Trắc nghiệm nhiều đáp án'; ?></p>
                                                        <p><strong>Mức độ:</strong> <?php echo $levelLabels[$q['level']] ?? htmlspecialchars($q['level']); ?></p>
                                                        <p><strong>Các lựa chọn:</strong></p>
                                                        <ul class="list-unstyled">
                                                            <?php
                                                            $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                                                            $correctIndices = is_array($q['correct']) ? $q['correct'] : [$q['correct']];
                                                            foreach ($q['options'] as $idx => $opt):
                                                                $isCorrect = in_array($idx, $correctIndices);
                                                                $correctMark = $isCorrect ? ' <span class="badge bg-success">✓ Đúng</span>' : '';
                                                            ?>
                                                                <li><strong><?php echo $letters[$idx]; ?>.</strong> <?php echo htmlspecialchars($opt); ?><?php echo $correctMark; ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                      </div>
                                                      <div class="modal-footer">
                                                        <button type="button" class="btn btn-warning edit-question" data-topic-index="<?php echo $topicIndex; ?>" data-index="<?php echo $item['index']; ?>" data-flat-index="<?php echo $flatIndex; ?>" title="Sửa câu hỏi">
                                                            ✏️ Sửa
                                                        </button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                      </div>
                                                    </div>
                                                  </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (empty($questionsData)): ?>
        <div class="alert alert-info">Không có câu hỏi nào.</div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info">Vui lòng chọn khối và môn học để xem câu hỏi.</div>
<?php endif; ?>
