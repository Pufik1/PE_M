        </div>
    </main>

    <!-- Модальное окно редактирования заказа -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Редактирование заказа</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="editOrderForm" class="p-6">
                <input type="hidden" id="editOrderId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Номер заказа</label>
                        <input type="text" id="editOrderNumber" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Дата создания</label>
                        <input type="text" id="editCreatedAt" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" readonly>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Клиент</label>
                    <select id="editPartnerId" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Выберите клиента</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Статус</label>
                    <select id="editStatus" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="new">Новый</option>
                        <option value="processing">В обработке</option>
                        <option value="in_progress">В производстве</option>
                        <option value="shipped">Отгружен</option>
                        <option value="ready">Готов</option>
                        <option value="closed">Завершен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Сумма (BYN)</label>
                    <input type="number" step="0.01" id="editTotalAmount" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Комментарий</label>
                    <textarea id="editComment" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </form>
            
            <div class="flex justify-end space-x-3 px-6 pb-6">
                <button onclick="closeEditModal()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-semibold">
                    Отмена
                </button>
                <button onclick="saveOrder()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all font-semibold shadow-lg">
                    Сохранить
                </button>
            </div>
        </div>
    </div>

    <script>
    // Функции для работы с модальным окном
    function openEditModal(orderId) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
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
                    alert('Ошибка загрузки данных заказа');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка подключения к серверу');
            });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
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
