        </div>
    </main>
    <style>
        #editModal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        #editModal .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            max-width: 640px;
            width: 100%;
            margin: 16px;
            max-height: 90vh;
            overflow-y: auto;
            color: var(--text-primary);
        }
        #editModal .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
        }
        #editModal .modal-body {
            padding: 24px;
        }
        #editModal .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 0 24px 24px;
        }
    </style>

    <!-- Модальное окно редактирования заказа -->
    <div id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800" style="font-size: 18px; font-weight: 600; color: var(--text-primary);">Редактирование заказа</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors" style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="editOrderForm" class="modal-body">
                <input type="hidden" id="editOrderId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Номер заказа</label>
                        <input type="text" id="editOrderNumber" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" readonly style="width: 100%; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: var(--text-secondary); outline: none;">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Дата создания</label>
                        <input type="text" id="editCreatedAt" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" readonly style="width: 100%; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: var(--text-secondary); outline: none;">
                    </div>
                </div>
                
                <div class="mb-6" style="margin-bottom: 24px;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2" style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Клиент</label>
                    <select id="editPartnerId" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="width: 100%; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: var(--text-primary); outline: none;">
                        <option value="">Выберите клиента</option>
                    </select>
                </div>
                
                <div class="mb-6" style="margin-bottom: 24px;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2" style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Статус</label>
                    <select id="editStatus" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="width: 100%; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: var(--text-primary); outline: none;">
                        <option value="new">Новый</option>
                        <option value="processing">В обработке</option>
                        <option value="in_progress">В производстве</option>
                        <option value="shipped">Отгружен</option>
                        <option value="ready">Готов</option>
                        <option value="closed">Завершен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
                
                <div class="mb-6" style="margin-bottom: 24px;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2" style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Сумма (BYN)</label>
                    <input type="number" step="0.01" id="editTotalAmount" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="width: 100%; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: var(--text-primary); outline: none;">
                </div>
                
                <div class="mb-6" style="margin-bottom: 24px;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2" style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Комментарий</label>
                    <textarea id="editComment" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="width: 100%; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: var(--text-primary); outline: none; font-family: inherit;"></textarea>
                </div>
            </form>
            
            <div class="modal-footer">
                <button onclick="closeEditModal()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-semibold" style="padding: 10px 20px; background: var(--bg-card); color: var(--text-secondary); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Отмена
                </button>
                <button onclick="saveOrder()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all font-semibold shadow-lg" style="padding: 10px 20px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);">
                    Сохранить
                </button>
            </div>
        </div>
    </div>

    <script>
    // Функции для работы с модальным окном
    function openEditModal(orderId) {
        const modal = document.getElementById('editModal');
        modal.style.display = 'flex';
        document.getElementById('editOrderId').value = orderId;
        
        // Загрузка данных заказа
        fetch('api/get_order.php?id=' + orderId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.order;
                    document.getElementById('editOrderNumber').value = order.order_number;
                    document.getElementById('editCreatedAt').value = order.created_at;
                    document.getElementById('editPartnerId').value = order.partner_id;
                    document.getElementById('editStatus').value = order.status;
                    document.getElementById('editTotalAmount').value = order.total_amount_byn;
                    document.getElementById('editComment').value = order.comment || '';
                    
                    // Загрузка списка клиентов
                    loadPartners(order.partner_id);
                } else {
                    alert('Ошибка загрузки данных заказа: ' + (data.message || 'Неизвестная ошибка'));
                    closeEditModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка подключения к серверу');
                closeEditModal();
            });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    function loadPartners(selectedId) {
        fetch('api/get_partners.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('editPartnerId');
                select.innerHTML = '<option value="">Выберите клиента</option>';
                if (data.success) {
                    data.partners.forEach(partner => {
                        const option = document.createElement('option');
                        option.value = partner.id;
                        option.textContent = partner.name;
                        if (partner.id == selectedId) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading partners:', error));
    }
    
    function saveOrder() {
        const formData = new FormData();
        formData.append('id', document.getElementById('editOrderId').value);
        formData.append('partner_id', document.getElementById('editPartnerId').value);
        formData.append('status', document.getElementById('editStatus').value);
        formData.append('total_amount_byn', document.getElementById('editTotalAmount').value);
        formData.append('comment', document.getElementById('editComment').value);
        
        fetch('api/update_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Заказ успешно обновлен!');
                closeEditModal();
                location.reload();
            } else {
                alert('Ошибка при сохранении: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка подключения к серверу');
        });
    }
    
    function deleteOrder(orderId) {
        if (confirm('Вы уверены, что хотите удалить этот заказ?')) {
            fetch('api/delete_order.php?id=' + orderId, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Заказ успешно удален!');
                    location.reload();
                } else {
                    alert('Ошибка при удалении: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка подключения к серверу');
            });
        }
    }
    
    // Закрытие модального окна по клику вне его
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    </script>
    
</body>
</html>
