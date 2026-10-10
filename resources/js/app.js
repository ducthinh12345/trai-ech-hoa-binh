/*
 * Alpine được Livewire nạp sẵn; đăng ký component dùng chung trước khi Alpine khởi động.
 */
document.addEventListener('alpine:init', () => {
    /* Thông báo nổi góc màn hình. Nghe cả event Livewire `toast` và flash session in sẵn trong layout. */
    window.Alpine.data('toaster', (initial = []) => ({
        toasts: [],
        init() {
            initial.forEach((t) => this.push(t));
            window.addEventListener('toast', (e) => this.push(e.detail));
        },
        push({ type = 'success', message = '' }) {
            if (!message) return;
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message });
            setTimeout(() => this.dismiss(id), 4200);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
    }));

    /* Thư viện ảnh trang sản phẩm: ảnh lớn + thumbnail + vuốt trên mobile. */
    window.Alpine.data('gallery', (count) => ({
        active: 0,
        startX: null,
        go(i) {
            this.active = (i + count) % count;
        },
        touchStart(e) {
            this.startX = e.touches[0].clientX;
        },
        touchEnd(e) {
            if (this.startX === null) return;
            const dx = e.changedTouches[0].clientX - this.startX;
            if (Math.abs(dx) > 40) this.go(this.active + (dx < 0 ? 1 : -1));
            this.startX = null;
        },
    }));

    /* Slider banner trang chủ, tự chạy và dừng khi rê chuột. */
    window.Alpine.data('slider', (count, interval = 5500) => ({
        active: 0,
        timer: null,
        init() {
            this.start();
        },
        start() {
            if (count < 2) return;
            this.stop();
            this.timer = setInterval(() => this.next(), interval);
        },
        stop() {
            clearInterval(this.timer);
        },
        next() {
            this.active = (this.active + 1) % count;
        },
        prev() {
            this.active = (this.active - 1 + count) % count;
        },
    }));
});
