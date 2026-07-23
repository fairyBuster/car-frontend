<?php
declare(strict_types=1);

if (!isset($marketNotificationData) || !is_array($marketNotificationData) || empty($marketNotificationData['items'])) {
    return;
}
?>
<div class="market-notice" id="marketNotice" hidden>
    <div class="market-notice-card">
        <div class="market-notice-copy">
            <span class="market-notice-pill">Market Notice</span>
            <strong id="marketNoticeTitle">Market is active</strong>
            <p id="marketNoticeText">You have a fish ready for sale.</p>
        </div>
        <div class="market-notice-actions">
            <a href="aquarium.php" class="market-notice-button">Go to Aquarium</a>
            <button type="button" class="market-notice-close" id="marketNoticeClose" aria-label="Close">×</button>
        </div>
    </div>
</div>
<script id="marketNoticeData" type="application/json"><?= json_encode($marketNotificationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script>
    (() => {
        const payloadNode = document.getElementById('marketNoticeData');
        const notice = document.getElementById('marketNotice');
        const closeButton = document.getElementById('marketNoticeClose');
        const noticeTitle = document.getElementById('marketNoticeTitle');
        const noticeText = document.getElementById('marketNoticeText');

        if (!payloadNode || !notice || !closeButton || !noticeTitle || !noticeText) {
            return;
        }

        const payload = JSON.parse(payloadNode.textContent);
        const items = Array.isArray(payload.items) ? payload.items.slice() : [];
        const shownKeys = new Set();
        let activeTimer = null;
        let hideTimer = null;

        function notificationKey(item) {
            return `${item.key}:${item.market_available_at}`;
        }

        function hasBeenShown(item) {
            return sessionStorage.getItem(`market_notice:${notificationKey(item)}`) === '1';
        }

        function markAsShown(item) {
            sessionStorage.setItem(`market_notice:${notificationKey(item)}`, '1');
            shownKeys.add(notificationKey(item));
        }

        function showNotice(messageTitle, messageText) {
            if (hideTimer) {
                window.clearTimeout(hideTimer);
            }

            noticeTitle.textContent = messageTitle;
            noticeText.textContent = messageText;
            notice.hidden = false;
            window.requestAnimationFrame(() => {
                notice.classList.add('is-visible');
            });

            hideTimer = window.setTimeout(() => {
                notice.classList.remove('is-visible');
                window.setTimeout(() => {
                    if (!notice.classList.contains('is-visible')) {
                        notice.hidden = true;
                    }
                }, 380);
            }, 9000);
        }

        function nextUpcomingItem() {
            const now = Date.now();
            return items
                .filter((item) => !shownKeys.has(notificationKey(item)) && !hasBeenShown(item))
                .map((item) => ({
                    ...item,
                    remaining: Math.max(0, new Date(item.market_available_at).getTime() - now),
                }))
                .sort((left, right) => left.remaining - right.remaining)[0] || null;
        }

        function scheduleNext() {
            if (activeTimer) {
                window.clearTimeout(activeTimer);
            }

            const readyItems = items.filter((item) => {
                const isReady = new Date(item.market_available_at).getTime() <= Date.now();
                return isReady && !shownKeys.has(notificationKey(item)) && !hasBeenShown(item);
            });

            if (readyItems.length > 0) {
                const first = readyItems[0];
                markAsShown(first);
                showNotice('Market is active', `${first.name} is now ready for sale in the market.`);
                activeTimer = window.setTimeout(scheduleNext, 9600);
                return;
            }

            const upcoming = nextUpcomingItem();
            if (!upcoming) {
                return;
            }

            activeTimer = window.setTimeout(() => {
                markAsShown(upcoming);
                showNotice('Market is active', `${upcoming.name} is now ready for sale in the market.`);
                activeTimer = window.setTimeout(scheduleNext, 9600);
            }, upcoming.remaining);
        }

        closeButton.addEventListener('click', () => {
            notice.classList.remove('is-visible');
            window.setTimeout(() => {
                if (!notice.classList.contains('is-visible')) {
                    notice.hidden = true;
                }
            }, 380);
        });

        window.aquaMarketNotifier = {
            addItem(item) {
                if (!item || !item.key || !item.market_available_at) {
                    return;
                }

                items.push(item);
                scheduleNext();
            },
        };

        window.setTimeout(scheduleNext, 600);
    })();
</script>
