<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lỗi nghiệp vụ hiển thị thẳng cho khách (hết hàng, mã không hợp lệ...). Message luôn là tiếng Việt thân thiện.
 */
class CartException extends RuntimeException {}
