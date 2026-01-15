<style>
    .loading-char {
        font-weight: bold;
        font-size: 1.5rem;
        color: #007bff;
        opacity: 0.2;
        animation: blink 1.5s infinite;
        display: inline-block;
    }

    .loading-char:nth-child(1) {
        animation-delay: 0s;
    }

    .loading-char:nth-child(2) {
        animation-delay: 0.1s;
    }

    .loading-char:nth-child(3) {
        animation-delay: 0.2s;
    }

    .loading-char:nth-child(4) {
        animation-delay: 0.3s;
    }

    .loading-char:nth-child(5) {
        animation-delay: 0.4s;
    }

    .loading-char:nth-child(6) {
        animation-delay: 0.5s;
    }

    .loading-char:nth-child(7) {
        animation-delay: 0.6s;
    }

    .loading-char:nth-child(8) {
        animation-delay: 0.7s;
    }

    .loading-char:nth-child(9) {
        animation-delay: 0.8s;
    }

    .loading-char:nth-child(10) {
        animation-delay: 0.9s;
    }

    #basic-6 th:nth-child(3),
    #basic-6 td:nth-child(3) {
        width: 80px;
        white-space: nowrap;
        text-align: center;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 0.2;
        }

        50% {
            opacity: 1;
        }
    }

</style>
