<!-- Created by Maria -->

<!DOCTYPE html>
<html>

<head>
    <title>Workspace-Planner</title>
</head>

<body>

</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive To-Do List</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        [data-theme="dark"] {
            --background: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--background);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .todo-container {
            background-color: var(--card-bg);
            width: 100%;
            max-width: 540px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            transition: background-color 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.25rem;
            padding: 5px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s, background-color 0.2s;
        }

        .theme-toggle:hover {
            color: var(--primary);
            background-color: var(--border);
        }

        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .todo-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            background-color: transparent;
            color: var(--text-main);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .todo-input:focus {
            border-color: var(--primary);
        }

        .add-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .add-btn:hover {
            background-color: var(--primary-hover);
        }

        .add-btn:active {
            transform: scale(0.98);
        }

        .filters {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .filter-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 6px 12px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: white;
        }

        .filter-btn:not(.active):hover {
            background-color: var(--border);
            color: var(--text-main);
        }

        .todo-list {
            list-style: none;
            max-height: 350px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 5px;
        }

        .todo-list::-webkit-scrollbar {
            width: 6px;
        }

        .todo-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .todo-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }

        .todo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background-color: var(--background);
            border-radius: var(--radius);
            margin-bottom: 10px;
            border: 1px solid var(--border);
            transition: all 0.25s ease;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .todo-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .todo-item.completed {
            opacity: 0.7;
        }

        .todo-item-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--text-muted);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .todo-item.completed .custom-checkbox {
            background-color: var(--success);
            border-color: var(--success);
        }

        .custom-checkbox::after {
            content: "✓";
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: none;
        }

        .todo-item.completed .custom-checkbox::after {
            display: block;
        }

        .todo-text {
            font-size: 1rem;
            color: var(--text-main);
            transition: text-decoration 0.2s, color 0.2s;
            word-break: break-word;
        }

        .todo-item.completed .todo-text {
            text-decoration: line-through;
            color: var(--text-muted);
        }

        .delete-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 5px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .delete-btn:hover {
            color: var(--danger);
            background-color: rgba(239, 68, 68, 0.1);
        }

        .todo-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }

        .clear-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            cursor: pointer;
            transition: color 0.2s;
        }

        .clear-btn:hover {
            color: var(--danger);
        }

        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: 10px;
            stroke: var(--text-muted);
        }
    </style>
</head>

<body>

    <div class="todo-container">
        <div class="header">
            <h1>My Tasks</h1>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle Theme">
                <!-- Moon icon -->
                <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>
        </div>

        <form id="todoForm" class="input-group">
            <input type="text" id="todoInput" class="todo-input" placeholder="Add a new task..." autocomplete="off" required>
            <button type="submit" class="add-btn">Add</button>
        </form>

        <div class="filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="active">Active</button>
            <button class="filter-btn" data-filter="completed">Completed</button>
        </div>

        <ul class="todo-list" id="todoList"></ul>

        <div class="todo-footer">
            <span id="itemsLeft">0 items left</span>
            <button class="clear-btn" id="clearCompleted">Clear Completed</button>
        </div>
    </div>

    <script>
        // State Management
        let todos = JSON.parse(localStorage.getItem('todos')) || [];
        let currentFilter = 'all';

        // DOM Elements
        const todoForm = document.getElementById('todoForm');
        const todoInput = document.getElementById('todoInput');
        const todoList = document.getElementById('todoList');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const itemsLeftSpan = document.getElementById('itemsLeft');
        const clearCompletedBtn = document.getElementById('clearCompleted');
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');

        // Theme management
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.innerHTML = `<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>`;
            } else {
                themeIcon.innerHTML = `<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>`;
            }
        }

        // Save to Local Storage
        function saveToLocalStorage() {
            localStorage.setItem('todos', JSON.stringify(todos));
        }

        // Render To-Do List
        function render() {
            todoList.innerHTML = '';

            const filteredTodos = todos.filter(todo => {
                if (currentFilter === 'active') return !todo.completed;
                if (currentFilter === 'completed') return todo.completed;
                return true;
            });

            if (filteredTodos.length === 0) {
                renderEmptyState();
                updateFooterCounters();
                return;
            }

            filteredTodos.forEach(todo => {
                const li = document.createElement('li');
                li.className = `todo-item ${todo.completed ? 'completed' : ''}`;
                li.setAttribute('data-id', todo.id);

                li.innerHTML = `
                    <div class="todo-item-content">
                        <div class="custom-checkbox"></div>
                        <span class="todo-text">${escapeHTML(todo.text)}</span>
                    </div>
                    <button class="delete-btn" aria-label="Delete Task">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                `;

                // Toggle Event
                li.querySelector('.todo-item-content').addEventListener('click', () => {
                    toggleTodo(todo.id);
                });

                // Delete Event
                li.querySelector('.delete-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteTodo(todo.id);
                });

                todoList.appendChild(li);
            });

            updateFooterCounters();
        }

        function renderEmptyState() {
            let message = "No tasks yet! Add one above.";
            if (currentFilter === 'active') message = "No active tasks.";
            if (currentFilter === 'completed') message = "No completed tasks.";

            todoList.innerHTML = `
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <p>${message}</p>
                </div>
            `;
        }

        // Helper: Prevent XSS
        function escapeHTML(str) {
            return str.replace(/[&<>'"]/g,
                tag => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                } [tag] || tag)
            );
        }

        // Add Todo
        todoForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const text = todoInput.value.trim();
            if (text === '') return;

            const newTodo = {
                id: Date.now(),
                text: text,
                completed: false
            };

            todos.push(newTodo);
            saveToLocalStorage();
            render();
            todoInput.value = '';
            todoInput.focus();
        });

        // Toggle Todo
        function toggleTodo(id) {
            todos = todos.map(todo => {
                if (todo.id === id) {
                    return {
                        ...todo,
                        completed: !todo.completed
                    };
                }
                return todo;
            });
            saveToLocalStorage();
            render();
        }

        // Delete Todo
        function deleteTodo(id) {
            const element = document.querySelector(`[data-id="${id}"]`);
            if (element) {
                element.style.transform = 'translateX(30px)';
                element.style.opacity = '0';
                setTimeout(() => {
                    todos = todos.filter(todo => todo.id !== id);
                    saveToLocalStorage();
                    render();
                }, 250);
            } else {
                todos = todos.filter(todo => todo.id !== id);
                saveToLocalStorage();
                render();
            }
        }

        // Filter Logic
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                filterBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                currentFilter = e.target.getAttribute('data-filter');
                render();
            });
        });

        // Clear Completed
        clearCompletedBtn.addEventListener('click', () => {
            todos = todos.filter(todo => !todo.completed);
            saveToLocalStorage();
            render();
        });

        // Update Counters
        function updateFooterCounters() {
            const activeCount = todos.filter(todo => !todo.completed).length;
            itemsLeftSpan.textContent = `${activeCount} item${activeCount === 1 ? '' : 's'} left`;

            // Show/Hide Clear Completed button
            const hasCompleted = todos.some(todo => todo.completed);
            clearCompletedBtn.style.display = hasCompleted ? 'block' : 'none';
        }

        // Initial Render
        render();
    </script>
</body>

</html>
