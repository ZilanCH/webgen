import os
import secrets
import sqlite3
from datetime import datetime

from flask import Flask, abort, flash, g, redirect, render_template, request, session, url_for
from werkzeug.security import check_password_hash, generate_password_hash
from markupsafe import Markup

app = Flask(__name__)
app.config['DATABASE'] = os.environ.get('WEBGEN_DB', os.path.join(app.root_path, 'webgen.db'))
app.config['SECRET_KEY'] = os.environ.get('WEBGEN_SECRET', secrets.token_hex(16))


def get_db() -> sqlite3.Connection:
    if 'db' not in g:
        g.db = sqlite3.connect(app.config['DATABASE'])
        g.db.row_factory = sqlite3.Row
    return g.db


def close_db(_=None) -> None:
    db = g.pop('db', None)
    if db is not None:
        db.close()


def init_db() -> None:
    db = get_db()
    db.execute(
        """
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user',
            created_at TEXT NOT NULL
        );
        """
    )
    db.execute(
        """
        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            owner_id INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(owner_id) REFERENCES users(id)
        );
        """
    )
    db.commit()
    ensure_admin_seed()


def ensure_admin_seed() -> None:
    db = get_db()
    admin = db.execute("SELECT id FROM users WHERE role='admin' LIMIT 1").fetchone()
    if admin:
        return
    password = generate_password_hash('admin123')
    db.execute(
        "INSERT INTO users (email, name, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)",
        ('admin@example.com', 'Admin', password, 'admin', datetime.utcnow().isoformat()),
    )
    db.commit()


def load_logged_in_user():
    user_id = session.get('user_id')
    if not user_id:
        g.user = None
        return
    db = get_db()
    row = db.execute('SELECT * FROM users WHERE id=?', (user_id,)).fetchone()
    g.user = dict(row) if row else None


@app.before_request
def before_request():
    init_db()
    load_logged_in_user()


@app.teardown_appcontext
def teardown_db(exception):
    close_db()


def require_login():
    if g.user is None:
        return redirect(url_for('login', next=request.path))
    return None


def require_admin():
    if g.user is None:
        return redirect(url_for('login', next=request.path))
    if g.user['role'] != 'admin':
        abort(403)
    return None


def user_can_edit_page(page: sqlite3.Row) -> bool:
    return g.user and (g.user['role'] == 'admin' or page['owner_id'] == g.user['id'])


@app.template_filter('nl2br')
def nl2br(value: str) -> Markup:
    return Markup('<br>'.join(value.splitlines()))


@app.route('/')
def index():
    if g.user:
        return redirect(url_for('list_pages'))
    return redirect(url_for('login'))


@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email'].strip().lower()
        password = request.form['password']
        db = get_db()
        user = db.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        if user and check_password_hash(user['password_hash'], password):
            session.clear()
            session['user_id'] = user['id']
            flash('Logged in successfully.', 'success')
            return redirect(request.args.get('next') or url_for('index'))
        flash('Invalid credentials.', 'error')
    return render_template('login.html')


@app.route('/logout')
def logout():
    session.clear()
    flash('Logged out.', 'success')
    return redirect(url_for('login'))


@app.route('/pages')
def list_pages():
    if (resp := require_login()) is not None:
        return resp
    db = get_db()
    pages = db.execute(
        'SELECT p.*, u.name as owner_name FROM pages p JOIN users u ON p.owner_id=u.id WHERE p.owner_id=? ORDER BY p.created_at DESC',
        (g.user['id'],),
    ).fetchall()
    return render_template('pages/index.html', pages=pages)


@app.route('/pages/new', methods=['GET', 'POST'])
def create_page():
    if (resp := require_login()) is not None:
        return resp
    if request.method == 'POST':
        title = request.form['title'].strip()
        content = request.form['content'].strip()
        if not title or not content:
            flash('Title and content are required.', 'error')
            return render_template('pages/new.html', title=title, content=content)
        db = get_db()
        now = datetime.utcnow().isoformat()
        db.execute(
            'INSERT INTO pages (title, content, owner_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            (title, content, g.user['id'], now, now),
        )
        db.commit()
        flash('Page created.', 'success')
        return redirect(url_for('list_pages'))
    return render_template('pages/new.html')


@app.route('/pages/<int:page_id>')
def view_page(page_id: int):
    if (resp := require_login()) is not None:
        return resp
    db = get_db()
    page = db.execute(
        'SELECT p.*, u.name as owner_name FROM pages p JOIN users u ON p.owner_id=u.id WHERE p.id=?',
        (page_id,),
    ).fetchone()
    if page is None:
        abort(404)
    if not user_can_edit_page(page):
        abort(403)
    return render_template('pages/view.html', page=page)


@app.route('/pages/<int:page_id>/edit', methods=['GET', 'POST'])
def edit_page(page_id: int):
    if (resp := require_login()) is not None:
        return resp
    db = get_db()
    page = db.execute('SELECT * FROM pages WHERE id=?', (page_id,)).fetchone()
    if page is None:
        abort(404)
    if not user_can_edit_page(page):
        abort(403)
    if request.method == 'POST':
        title = request.form['title'].strip()
        content = request.form['content'].strip()
        if not title or not content:
            flash('Title and content are required.', 'error')
            return render_template('pages/edit.html', page=page)
        now = datetime.utcnow().isoformat()
        db.execute('UPDATE pages SET title=?, content=?, updated_at=? WHERE id=?', (title, content, now, page_id))
        db.commit()
        flash('Page updated.', 'success')
        return redirect(url_for('list_pages'))
    return render_template('pages/edit.html', page=page)


@app.route('/pages/<int:page_id>/delete', methods=['POST'])
def delete_page(page_id: int):
    if (resp := require_login()) is not None:
        return resp
    db = get_db()
    page = db.execute('SELECT * FROM pages WHERE id=?', (page_id,)).fetchone()
    if page is None:
        abort(404)
    if not user_can_edit_page(page):
        abort(403)
    db.execute('DELETE FROM pages WHERE id=?', (page_id,))
    db.commit()
    flash('Page deleted.', 'success')
    return redirect(url_for('list_pages'))


@app.route('/admin')
def admin_dashboard():
    if (resp := require_admin()) is not None:
        return resp
    return render_template('admin/dashboard.html')


@app.route('/admin/pages')
def admin_pages():
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    pages = db.execute(
        'SELECT p.*, u.name as owner_name, u.email as owner_email FROM pages p JOIN users u ON p.owner_id=u.id ORDER BY p.created_at DESC'
    ).fetchall()
    return render_template('admin/pages/index.html', pages=pages)


@app.route('/admin/pages/<int:page_id>')
def admin_view_page(page_id: int):
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    page = db.execute(
        'SELECT p.*, u.name as owner_name, u.email as owner_email FROM pages p JOIN users u ON p.owner_id=u.id WHERE p.id=?',
        (page_id,),
    ).fetchone()
    if page is None:
        abort(404)
    return render_template('admin/pages/view.html', page=page)


@app.route('/admin/pages/<int:page_id>/delete', methods=['POST'])
def admin_delete_page(page_id: int):
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    db.execute('DELETE FROM pages WHERE id=?', (page_id,))
    db.commit()
    flash('Page deleted.', 'success')
    return redirect(url_for('admin_pages'))


@app.route('/admin/users')
def admin_users():
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    users = db.execute('SELECT * FROM users ORDER BY created_at DESC').fetchall()
    return render_template('admin/users/index.html', users=users)


@app.route('/admin/users/new', methods=['GET', 'POST'])
def admin_create_user():
    if (resp := require_admin()) is not None:
        return resp
    if request.method == 'POST':
        name = request.form['name'].strip()
        email = request.form['email'].strip().lower()
        role = request.form.get('role', 'user')
        password = request.form['password']
        if not name or not email or not password:
            flash('Name, email, and password are required.', 'error')
            return render_template('admin/users/new.html', name=name, email=email, role=role)
        db = get_db()
        try:
            db.execute(
                'INSERT INTO users (email, name, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)',
                (email, name, generate_password_hash(password), role, datetime.utcnow().isoformat()),
            )
            db.commit()
            flash('User created.', 'success')
            return redirect(url_for('admin_users'))
        except sqlite3.IntegrityError:
            flash('Email already exists.', 'error')
    return render_template('admin/users/new.html')


@app.route('/admin/users/<int:user_id>/edit', methods=['GET', 'POST'])
def admin_edit_user(user_id: int):
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    user = db.execute('SELECT * FROM users WHERE id=?', (user_id,)).fetchone()
    if user is None:
        abort(404)
    if request.method == 'POST':
        name = request.form['name'].strip()
        email = request.form['email'].strip().lower()
        role = request.form.get('role', 'user')
        password = request.form.get('password', '').strip()
        if not name or not email:
            flash('Name and email are required.', 'error')
            return render_template('admin/users/edit.html', user=user)
        db.execute('UPDATE users SET name=?, email=?, role=? WHERE id=?', (name, email, role, user_id))
        if password:
            db.execute('UPDATE users SET password_hash=? WHERE id=?', (generate_password_hash(password), user_id))
        db.commit()
        flash('User updated.', 'success')
        return redirect(url_for('admin_users'))
    return render_template('admin/users/edit.html', user=user)


@app.route('/admin/users/<int:user_id>/reset', methods=['POST'])
def admin_reset_password(user_id: int):
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    user = db.execute('SELECT * FROM users WHERE id=?', (user_id,)).fetchone()
    if user is None:
        abort(404)
    new_password = request.form.get('new_password', '').strip() or 'changeme123'
    db.execute('UPDATE users SET password_hash=? WHERE id=?', (generate_password_hash(new_password), user_id))
    db.commit()
    flash(f"Password reset. New password: {new_password}", 'success')
    return redirect(url_for('admin_users'))


@app.route('/admin/users/<int:user_id>/delete', methods=['POST'])
def admin_delete_user(user_id: int):
    if (resp := require_admin()) is not None:
        return resp
    db = get_db()
    db.execute('DELETE FROM pages WHERE owner_id=?', (user_id,))
    db.execute('DELETE FROM users WHERE id=?', (user_id,))
    db.commit()
    flash('User deleted.', 'success')
    return redirect(url_for('admin_users'))


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=int(os.environ.get('PORT', 5000)))
