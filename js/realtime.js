// js/realtime.js

document.addEventListener('DOMContentLoaded', function() {
    const NOTIFICATION_POLL_INTERVAL = 5000; // 5 seconds
    const MESSAGE_POLL_INTERVAL = 3000;      // 3 seconds
    const appRoot = (window.DFPS_APP_ROOT || '').replace(/\/$/, '');

    function appUrl(path) {
        const rawPath = String(path || '');
        if (appRoot && rawPath.startsWith(appRoot + '/')) {
            return rawPath;
        }
        if (!appRoot && rawPath.startsWith('/')) {
            return rawPath;
        }
        const cleanPath = rawPath.replace(/^\/+/, '');
        return (appRoot ? appRoot : '') + '/' + cleanPath;
    }

    const currentPath = window.location.pathname;
    const relativePath = appRoot && currentPath.startsWith(appRoot) ? currentPath.slice(appRoot.length) : currentPath;
    const pathSegments = relativePath.replace(/^\/+/, '').split('/').filter(Boolean);
    const currentSection = ['buyer', 'farmer', 'da'].includes(pathSegments[0]) ? pathSegments[0] : 'buyer';

    function isSelfNotificationRedirect(href) {
        if (!href) {
            return false;
        }

        try {
            const url = new URL(href, window.location.origin);
            const redirect = url.searchParams.get('redirect') || '';
            const decodedRedirect = decodeURIComponent(redirect).replace(/\\/g, '/').replace(/^(\.\.\/)+/, '').replace(/^\/+/, '');

            if (!decodedRedirect) {
                return false;
            }

            return decodedRedirect === 'notification.php' ||
                decodedRedirect === `${currentSection}/notification.php` ||
                decodedRedirect === `${currentSection}/notification`;
        } catch (err) {
            return false;
        }
    }

    // Poll for System Alerts (Broadcasts) state
    let globalLastNotifId = -1; // Use -1 to indicate uninitialized

    // Update Notification Badge
    function updateNotificationBadge() {
        fetch(appUrl('action/Notification/get_unread_count.php') + '?t=' + new Date().getTime())
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // Target bell icon specifically in header and sidebar
                const bellLinks = document.querySelectorAll('.header-item[href*="/notification"], .sidebar-link[href*="/notification"]');
                updateBadgeForElements(bellLinks, data.unread_count);
            })
            .catch(err => console.error('Error fetching notification count:', err));
    }

    // Update Message Badge
    function updateMessageBadge() {
        fetch(appUrl('action/Message/get_unread_count.php') + '?t=' + new Date().getTime())
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // Target message icon specifically in header and sidebar
                const msgLinks = document.querySelectorAll('.header-item[href*="/message"], .sidebar-link[href*="/message"]');
                updateBadgeForElements(msgLinks, data.unread_count);
            })
            .catch(err => console.error('Error fetching message count:', err));
    }

    function updateBadgeForElements(links, count) {
        links.forEach(link => {
            let badge = link.querySelector('.badge');
            if (count > 0) {
                const displayCount = count > 99 ? '99+' : count;
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge rounded-pill bg-danger';
                    link.appendChild(badge);
                }
                badge.textContent = displayCount;
                badge.style.setProperty('display', 'inline-block', 'important');
            } else if (badge) {
                badge.style.display = 'none';
            }
        });
    }

    // Polling for counts
    setInterval(() => {
        updateNotificationBadge();
        updateMessageBadge();
        fetchConversations();
        pollSystemAlerts();
    }, NOTIFICATION_POLL_INTERVAL);
    
    updateNotificationBadge();
    updateMessageBadge();
    pollSystemAlerts();

    // Poll for System Alerts (Broadcasts)
    function initializeLastNotifId() {
        fetch(appUrl('action/Notification/get_new_notifications.php') + '?last_id=0')
            .then(r => r.json())
            .then(data => {
                if (data.notifications && data.notifications.length > 0) {
                    globalLastNotifId = data.notifications[data.notifications.length - 1].id;
                } else {
                    globalLastNotifId = 0; // Ready to receive from scratch
                }
            })
            .catch(err => {
                console.error('Error initializing notifications:', err);
                globalLastNotifId = 0; // Fallback to 0 to at least try polling
            });
    }
    initializeLastNotifId();

    function pollSystemAlerts() {
        if (globalLastNotifId === -1) return; // Wait for initialization

        fetch(appUrl('action/Notification/get_new_notifications.php') + `?last_id=${globalLastNotifId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Get as text first to check if it is JSON
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response from get_new_notifications.php:', text);
                    throw e;
                }
            })
            .then(data => {
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        // Update the last ID so we don't show the same alert twice
                                                    if (notif.id > globalLastNotifId) {
                                                        globalLastNotifId = notif.id;
                                                        // Only show modal for SYSTEM_ALERT or ANNOUNCEMENT, or any type except NEW_MESSAGE
                                                        if (notif.type === 'SYSTEM_ALERT' || notif.type === 'ANNOUNCEMENT' || notif.type !== 'NEW_MESSAGE') {
                                                            showSystemAlertModal(notif);
                                                        }
                                                    }                    });
                }
            })
            .catch(err => console.error('Error polling system alerts:', err));
    }

    function showSystemAlertModal(notif) {
        const modalEl = document.getElementById('systemAlertModal');
        if (!modalEl) return;

        const titleEl = document.getElementById('alertTitle');
        const bodyEl = document.getElementById('alertBody');
        const linkEl = document.getElementById('alertLink');

        if (titleEl) titleEl.textContent = notif.title;
        if (bodyEl) bodyEl.innerHTML = notif.body.replace(/\n/g, '<br>');
        
        if (linkEl) {
            if (notif.link && notif.link !== 'javascript:void(0)') {
                // If it's already a mark_read link, use it. Otherwise wrap it.
                    if (notif.link.includes('mark_read.php')) {
                        linkEl.href = notif.link;
                    } else {
                        linkEl.href = appUrl('action/Notification/mark_read.php') + `?id=${notif.id}&redirect=${encodeURIComponent(notif.link)}`;
                    }
                linkEl.style.display = 'inline-block';
            } else {
                linkEl.style.display = 'none';
            }
        }

        // Mark as read in background via AJAX
        fetch(appUrl('action/Notification/mark_read.php') + `?id=${notif.id}`)
            .then(() => updateNotificationBadge())
            .catch(err => console.error('Error marking as read:', err));

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Refresh Conversation List
    function fetchConversations() {
        const convScroll = document.querySelector('.conversations-scroll');
        if (!convScroll) return;

        const view = convScroll.getAttribute('data-view') || 'active';
        const selectedId = convScroll.getAttribute('data-selected');
        const searchInput = document.getElementById('conv-search');
        const query = searchInput ? searchInput.value : '';

        fetch(appUrl('action/Message/get_conversations.php') + `?view=${view}&q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.conversations) {
                    renderConversations(data.conversations, convScroll, selectedId, view);
                }
            })
            .catch(err => console.error('Error fetching conversations:', err));
    }

    // Handle search input with debounce
    const searchInput = document.getElementById('conv-search');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchConversations();
            }, 300);
        });
    }

    function renderConversations(conversations, container, selectedId, view) {
        if (conversations.length === 0) {
            container.innerHTML = `<div class="text-center text-muted p-4"><small>No ${view} conversations.</small></div>`;
            return;
        }

        let html = '';
        conversations.forEach(conv => {
            const isActive = (selectedId == conv.conversation_id);
            const dispMsg = conv.last_message_deleted ? 'Message removed' : (conv.last_message || 'No messages yet');
            const unreadBadge = conv.unread_count > 0 ? `<span class="badge rounded-pill bg-danger" style="font-size: 0.65rem;">${conv.unread_count}</span>` : '';
            const msgClass = (conv.last_message_deleted ? 'fst-italic' : '') + (conv.unread_count > 0 ? ' fw-bold text-dark' : '');
            
            // Profile Picture or Icon
            let avatarContent = '';
            if (conv.participant_profile_picture) {
                avatarContent = `<img src="${appUrl(conv.participant_profile_picture)}" class="w-100 h-100" style="object-fit: cover;" onerror="this.parentElement.innerHTML='<i class=\'bi bi-person-circle\' style=\'font-size: 1.5rem;\'></i>'">`;
            } else {
                avatarContent = `<i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>`;
            }

            // Basic HTML escaping for safety
            const fullName = (conv.first_name + ' ' + conv.last_name).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const safeMsg = dispMsg.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            html += `
                <a href="${appUrl(currentSection + '/message')}?conv_id=${conv.conversation_id}&view=${view}" class="conv-item ${isActive ? 'active' : ''}">
                    <div class="conv-avatar overflow-hidden">${avatarContent}</div>
                    <div class="conv-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="conv-name text-truncate" style="max-width: 140px;">${fullName}</div>
                            ${unreadBadge}
                        </div>
                        <div class="conv-last-msg ${msgClass.trim()}">${safeMsg}</div>
                    </div>
                </a>
            `;
        });
        container.innerHTML = html;
    }

    // Update Notification List if on notification page
    const notificationList = document.querySelector('.notification-list');
    if (notificationList) {
        // Find current last ID
        let lastNotifId = 0;
        // In the original PHP, we didn't add the ID to the DOM.
        // We'll need to update the PHP to add it, or just start polling from 0 and handle duplicates (not ideal).
        // Let's assume we'll update the PHP to add data-id.
        
        const getNewNotifications = () => {
            const items = notificationList.querySelectorAll('.notification-item[data-id]');
            let currentLastId = 0;
            items.forEach(item => {
                const id = parseInt(item.getAttribute('data-id'));
                if (id > currentLastId) currentLastId = id;
            });

            fetch(appUrl('action/Notification/get_new_notifications.php') + `?last_id=${currentLastId}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON from get_new_notifications.php (list):', text);
                        throw e;
                    }
                })
                .then(data => {
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.reverse().forEach(notif => {
                            appendNotification(notif);
                        });
                        // Remove "no notifications" message if it exists
                        const emptyMsg = notificationList.querySelector('.text-center.text-muted');
                        if (emptyMsg) emptyMsg.remove();
                    }
                })
                .catch(err => console.error('Error fetching new notifications:', err));
        };

        function getIconClass(type) {
            switch (type) {
                case 'NEW_MESSAGE': return 'bi-chat-dots-fill';
                case 'INTEREST_ACCEPTED': return 'bi-check-circle-fill';
                case 'POST_UPDATE': return 'bi-arrow-up-circle-fill';
                case 'ANNOUNCEMENT': return 'bi-megaphone-fill';
                case 'SYSTEM_ALERT': return 'bi-exclamation-triangle-fill';
                default: return 'bi-info-circle-fill';
            }
        }

        function appendNotification(notif) {
            // Skip rendering NEW_MESSAGE notifications
            if (notif.type === 'NEW_MESSAGE') {
                return;
            }
            const item = document.createElement('div');
            item.className = `notification-item ${!notif.is_read ? 'notification-unread' : ''}`;
            item.setAttribute('data-id', notif.id);
            item.setAttribute('data-title', notif.title);
            item.setAttribute('data-body', notif.body);
            
            // Re-calculate viewLink for the attribute
            const viewLink = notif.link ? appUrl(`action/Notification/mark_read.php?id=${notif.id}&redirect=${encodeURIComponent(notif.link)}`) : '';
            item.setAttribute('data-link', viewLink);
            
            const timeStr = new Date(notif.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });

            item.innerHTML = `
                <div class="notification-icon">
                    <i class="bi ${getIconClass(notif.type)}"></i>
                </div>
                <div class="notification-content">
                    <h6 class="mb-0">${notif.title}</h6>
                    <p class="mb-0 text-truncate" style="max-width: 500px;">${notif.body}</p>
                    <span class="time">${timeStr}</span>
                </div>
                <div class="notification-actions">
                    <a href="${viewLink}" class="btn btn-sm btn-primary ${!notif.link ? 'disabled' : ''}">View</a>
                    <a href="${appUrl(`action/Notification/dismiss.php?id=${notif.id}`)}" class="btn btn-sm btn-outline-secondary" title="Dismiss"><i class="bi bi-x"></i></a>
                </div>
            `;
            notificationList.prepend(item);
        }

        setInterval(getNewNotifications, NOTIFICATION_POLL_INTERVAL);

        // Only intercept View links that would just redirect back to the same notification page.
        notificationList.addEventListener('click', function(e) {
            const viewButton = e.target.closest('.notification-actions .btn-primary');
            if (!viewButton) {
                return;
            }

            if (!isSelfNotificationRedirect(viewButton.href)) {
                return;
            }

            e.preventDefault();

            const item = viewButton.closest('.notification-item');
            if (!item) {
                return;
            }

            const notif = {
                id: item.getAttribute('data-id'),
                title: item.getAttribute('data-title'),
                body: item.getAttribute('data-body'),
                link: ''
            };

            showSystemAlertModal(notif);
            item.classList.remove('notification-unread');
        });
    }

    // Handle Real-time Messages if on message page
    const messageContainer = document.getElementById('message-container');
    if (messageContainer) {
        let convId = messageContainer.getAttribute('data-conv-id');
        if (!convId) {
            const urlParams = new URLSearchParams(window.location.search);
            convId = urlParams.get('conv_id');
        }
        
        if (convId) {
            function fetchNewMessages() {
                const currentLastId = messageContainer.getAttribute('data-last-id') || 0;
                
                fetch(appUrl(`action/Message/get_new_messages.php?conv_id=${convId}&last_id=${currentLastId}&update_read=true`))
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        // Handle new messages
                        if (data.messages && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                appendMessage(msg);
                                messageContainer.setAttribute('data-last-id', msg.id);
                            });
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        }

                        // Handle deleted messages sync
                        if (data.deleted_ids && data.deleted_ids.length > 0) {
                            data.deleted_ids.forEach(id => {
                                updateDeletedMessageUI(id);
                            });
                        }
                    })
                    .catch(err => console.error('Error fetching messages:', err));
            }

            function updateDeletedMessageUI(messageId) {
                const row = document.querySelector(`.message-row[data-id="${messageId}"]`);
                if (!row) return;

                const body = row.querySelector('.message-body');
                if (body && !body.classList.contains('message-deleted')) {
                    body.classList.add('message-deleted');
                    body.innerHTML = "Message removed";
                    
                    // Remove the actions if they exist
                    const actions = row.querySelector('.message-actions');
                    if (actions) actions.remove();
                }
            }

            function appendMessage(msg) {
                // Check if message already exists (to avoid duplicates if polling overlaps)
                if (document.querySelector(`.message-row[data-id="${msg.id}"]`)) return;

                const isSent = (typeof currentUserId !== 'undefined') ? (msg.sender_id == currentUserId) : false;
                const row = document.createElement('div');
                row.className = `message-row ${isSent ? 'sent' : 'received'}`;
                row.setAttribute('data-id', msg.id);
                
                let avatarHtml = '';
                if (!isSent) {
                    let avatarContent = '';
                    if (typeof participantProfilePicture !== 'undefined' && participantProfilePicture) {
                        avatarContent = `<img src="${appUrl(participantProfilePicture)}" class="w-100 h-100" style="object-fit: cover;">`;
                    } else {
                        avatarContent = `<i class="bi bi-person-circle" style="font-size: 1.2rem;"></i>`;
                    }
                    avatarHtml = `<div class="message-avatar overflow-hidden">${avatarContent}</div>`;
                }

                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

                row.innerHTML = `
                    ${avatarHtml}
                    <div class="message ${isSent ? 'sent' : 'received'}">
                        <div class="message-body ${msg.is_deleted ? 'message-deleted' : ''}">
                            ${msg.is_deleted ? "Message removed" : msg.body.replace(/\n/g, '<br>')}
                        </div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
                messageContainer.appendChild(row);
            }

            setInterval(fetchNewMessages, MESSAGE_POLL_INTERVAL);
        }
    }
});
