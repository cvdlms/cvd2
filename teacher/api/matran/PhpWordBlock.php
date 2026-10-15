<?php
/**
 * PhpWordBlock.php
 *
 * PhpWord không kèm lớp PhpWord\Element\Container, nhưng TemplateProcessor::setComplexBlock()
 * lại ánh xạ tên lớp con (theo basename) sang Writer\Word2007\Element\<Name>.
 * Lớp này cung cấp một container rỗng tên "Container" để có thể chèn cả đoạn văn lẫn
 * bảng vào một ô macro của file mẫu.
 */

namespace Matran\PhpWord\Element;

class Container extends \PhpOffice\PhpWord\Element\AbstractContainer
{
    /**
     * Giả lập là container cấp Section để PhpWord cho phép chèn Text/Table/... vào.
     *
     * @var string
     */
    protected $container = 'Section';
}
