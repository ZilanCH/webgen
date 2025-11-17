import json
import os
import secrets
import threading
from datetime import datetime
from typing import Any, Dict, List, Optional

from flask import Flask, abort, flash, g, redirect, render_template, request, session, url_for
from markupsafe import Markup
from werkzeug.security import check_password_hash, generate_password_hash

app = Flask(__name__)
app.config['DATA_FILE'] = os.environ.get('WEBGEN_DATA', os.path.join(app.root_path, 'webgen.json'))
app.config['SECRET_KEY'] = os.environ.get('WEBGEN_SECRET', secrets.token_hex(16))

data_lock = threading.Lock()

def default_data() -> Dict[str, Any]:
    return {'users': [], 'pages': [], 'settings': {}, 'footer_links': []}

def read_data() -> Dict[str, Any]:
    if not os.path.exists(app.config['DATA_FILE']):
        return default_data()
    with open(app.config['DATA_FILE'], 'r', encoding='utf-8') as f:
        return json.load(f)

def write_data(data: Dict[str, Any]) -> None:
    target_dir = os.path.dirname(app.config['DATA_FILE']) or app.root_path
    os.makedirs(target_dir, exist_ok=True)
    with open(app.config['DATA_FILE'], 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2)

def ensure_data_loaded() -> None:
    if 'data' in g:
        return
    with data_lock:
        g.data = read_data()
        ensure_admin_seed()
        ensure_footer_seed()
        write_data(g.data)


def get_next_id(items: List[Dict[str, Any]]) -> int:
    return max((item.get('id', 0) for item in items), default=0) + 1


def ensure_admin_seed() -> None:
    if any(user.get('role') == 'admin' for user in g.data['users']):
        return
    g.data['users'].append(
        {
            'id': get_next_id(g.data['users']),
            'email': 'admin@example.com',
            'name': 'Admin',
            'password_hash': generate_password_hash('admin123'),
            'role': 'admin',
            'created_at': datetime.utcnow().isoformat(),
        }
    )


def ensure_footer_seed() -> None:
    if 'footer_text' in g.data['settings']:
        return
    g.data['settings']['footer_text'] = f"© {datetime.utcnow().year} WebGen"


def get_setting(key: str, default: str = '') -> str:
    ensure_data_loaded()
    return g.data['settings'].get(key, default)


def set_setting(key: str, value: str) -> None:
    ensure_data_loaded()
    g.data['settings'][key] = value
    persist_data()


def persist_data() -> None:
    with data_lock:
        write_data(g.data)


def load_logged_in_user() -> None:
    ensure_data_loaded()
    user_id = session.get('user_id')
    if not user_id:
        g.user = None
        return
    g.user = next((u for u in g.data['users'] if u['id'] == user_id), None)


def require_login():
    if g.user is None:
        return redirect(url_for('login', next=request.path))
    return None


def require_admin():
    if g.user is None:
        return redirect(url_for('login', next=request.path))
    if g.user.get('role') != 'admin':
        abort(403)
    return None


def user_can_edit_page(page: Dict[str, Any]) -> bool:
    return g.user and (g.user.get('role') == 'admin' or page.get('owner_id') == g.user.get('id'))


def load_footer_content() -> None:
    ensure_data_loaded()
    g.footer_text = g.data['settings'].get('footer_text', '')
    g.footer_links = sorted(
        g.data['footer_links'],
        key=lambda link: (link.get('position', 0), link.get('created_at', '')),
    )


@app.template_filter('nl2br')
def nl2br(value: str) -> Markup:
    return Markup('<br>'.join(value.splitlines()))


@app.before_request
def before_request():
    ensure_data_loaded()
    load_logged_in_user()
    load_footer_content()


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
        user = next((u for u in g.data['users'] if u['email'] == email), None)
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
    pages = [p for p in g.data['pages'] if p.get('owner_id') == g.user['id']]
    pages.sort(key=lambda p: p.get('created_at', ''), reverse=True)
    owned_pages = []
    for page in pages:
        owner = next((u for u in g.data['users'] if u['id'] == page.get('owner_id')), None)
        owned_pages.append({**page, 'owner_name': owner['name'] if owner else 'Unknown'})
    return render_template('pages/index.html', pages=owned_pages)


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
        now = datetime.utcnow().isoformat()
        g.data['pages'].append(
            {
                'id': get_next_id(g.data['pages']),
                'title': title,
                'content': content,
                'owner_id': g.user['id'],
                'created_at': now,
                'updated_at': now,
            }
        )
        persist_data()
        flash('Page created.', 'success')
        return redirect(url_for('list_pages'))
    return render_template('pages/new.html')


@app.route('/pages/<int:page_id>')
def view_page(page_id: int):
    if (resp := require_login()) is not None:
        return resp
    page = next((p for p in g.data['pages'] if p['id'] == page_id), None)
    if page is None:
        abort(404)
    if not user_can_edit_page(page):
        abort(403)
    owner = next((u for u in g.data['users'] if u['id'] == page.get('owner_id')), None)
    enriched_page = {**page, 'owner_name': owner['name'] if owner else 'Unknown'}
    return render_template('pages/view.html', page=enriched_page)


@app.route('/pages/<int:page_id>/edit', methods=['GET', 'POST'])
def edit_page(page_id: int):
    if (resp := require_login()) is not None:
        return resp
    page = next((p for p in g.data['pages'] if p['id'] == page_id), None)
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
        page['title'] = title
        page['content'] = content
        page['updated_at'] = datetime.utcnow().isoformat()
        persist_data()
        flash('Page updated.', 'success')
        return redirect(url_for('list_pages'))
    return render_template('pages/edit.html', page=page)


@app.route('/pages/<int:page_id>/delete', methods=['POST'])
def delete_page(page_id: int):
    if (resp := require_login()) is not None:
        return resp
    page = next((p for p in g.data['pages'] if p['id'] == page_id), None)
    if page is None:
        abort(404)
    if not user_can_edit_page(page):
        abort(403)
    g.data['pages'] = [p for p in g.data['pages'] if p['id'] != page_id]
    persist_data()
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
    pages = sorted(g.data['pages'], key=lambda p: p.get('created_at', ''), reverse=True)
    enriched_pages = []
    for page in pages:
        owner = next((u for u in g.data['users'] if u['id'] == page.get('owner_id')), None)
        enriched_pages.append(
            {
                **page,
                'owner_name': owner['name'] if owner else 'Unknown',
                'owner_email': owner['email'] if owner else 'Unknown',
            }
        )
    return render_template('admin/pages/index.html', pages=enriched_pages)


@app.route('/admin/pages/<int:page_id>')
def admin_view_page(page_id: int):
    if (resp := require_admin()) is not None:
        return resp
    page = next((p for p in g.data['pages'] if p['id'] == page_id), None)
    if page is None:
        abort(404)
    owner = next((u for u in g.data['users'] if u['id'] == page.get('owner_id')), None)
    enriched_page = {
        **page,
        'owner_name': owner['name'] if owner else 'Unknown',
        'owner_email': owner['email'] if owner else 'Unknown',
    }
    return render_template('admin/pages/view.html', page=enriched_page)


@app.route('/admin/pages/<int:page_id>/delete', methods=['POST'])
def admin_delete_page(page_id: int):
    if (resp := require_admin()) is not None:
        return resp
    g.data['pages'] = [p for p in g.data['pages'] if p['id'] != page_id]
    persist_data()
    flash('Page deleted.', 'success')
    return redirect(url_for('admin_pages'))


@app.route('/admin/users')
def admin_users():
    if (resp := require_admin()) is not None:
        return resp
    users = sorted(g.data['users'], key=lambda u: u.get('created_at', ''), reverse=True)
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
        if any(u['email'] == email for u in g.data['users']):
            flash('Email already exists.', 'error')
            return render_template('admin/users/new.html', name=name, email=email, role=role)
        g.data['users'].append(
            {
                'id': get_next_id(g.data['users']),
                'email': email,
                'name': name,
                'password_hash': generate_password_hash(password),
                'role': role,
                'created_at': datetime.utcnow().isoformat(),
            }
        )
        persist_data()
        flash('User created.', 'success')
        return redirect(url_for('admin_users'))
    return render_template('admin/users/new.html')


@app.route('/admin/users/<int:user_id>/edit', methods=['GET', 'POST'])
def admin_edit_user(user_id: int):
    if (resp := require_admin()) is not None:
        return resp
    user = next((u for u in g.data['users'] if u['id'] == user_id), None)
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
        if any(u['email'] == email and u['id'] != user_id for u in g.data['users']):
            flash('Email already exists.', 'error')
            return render_template('admin/users/edit.html', user=user)
        user.update({'name': name, 'email': email, 'role': role})
        if password:
            user['password_hash'] = generate_password_hash(password)
        persist_data()
        flash('User updated.', 'success')
        return redirect(url_for('admin_users'))
    return render_template('admin/users/edit.html', user=user)


@app.route('/admin/users/<int:user_id>/reset', methods=['POST'])
def admin_reset_password(user_id: int):
    if (resp := require_admin()) is not None:
        return resp
    user = next((u for u in g.data['users'] if u['id'] == user_id), None)
    if user is None:
        abort(404)
    new_password = request.form.get('new_password', '').strip() or 'changeme123'
    user['password_hash'] = generate_password_hash(new_password)
    persist_data()
    flash(f"Password reset. New password: {new_password}", 'success')
    return redirect(url_for('admin_users'))


@app.route('/admin/users/<int:user_id>/delete', methods=['POST'])
def admin_delete_user(user_id: int):
    if (resp := require_admin()) is not None:
        return resp
    g.data['pages'] = [p for p in g.data['pages'] if p.get('owner_id') != user_id]
    g.data['users'] = [u for u in g.data['users'] if u['id'] != user_id]
    persist_data()
    flash('User deleted.', 'success')
    return redirect(url_for('admin_users'))


@app.route('/admin/footer', methods=['GET', 'POST'])
def admin_footer():
    if (resp := require_admin()) is not None:
        return resp
    if request.method == 'POST':
        footer_text = request.form.get('footer_text', '').strip()
        set_setting('footer_text', footer_text)
        flash('Footer updated.', 'success')
        return redirect(url_for('admin_footer'))
    links = sorted(g.data['footer_links'], key=lambda l: (l.get('position', 0), l.get('created_at', '')))
    return render_template('admin/footer/index.html', footer_text=g.footer_text, links=links)


@app.route('/admin/footer/links/new', methods=['GET', 'POST'])
def admin_create_footer_link():
    if (resp := require_admin()) is not None:
        return resp
    if request.method == 'POST':
        label = request.form['label'].strip()
        url = request.form['url'].strip()
        position_raw = request.form.get('position', '0').strip()
        try:
            position = int(position_raw) if position_raw else 0
        except ValueError:
            flash('Position must be a number.', 'error')
            return render_template('admin/footer/new.html', label=label, url=url, position=position_raw)
        if not label or not url:
            flash('Label and URL are required.', 'error')
            return render_template('admin/footer/new.html', label=label, url=url, position=position_raw)
        g.data['footer_links'].append(
            {
                'id': get_next_id(g.data['footer_links']),
                'label': label,
                'url': url,
                'position': position,
                'created_at': datetime.utcnow().isoformat(),
            }
        )
        persist_data()
        flash('Footer link added.', 'success')
        return redirect(url_for('admin_footer'))
    return render_template('admin/footer/new.html')


@app.route('/admin/footer/links/<int:link_id>/edit', methods=['GET', 'POST'])
def admin_edit_footer_link(link_id: int):
    if (resp := require_admin()) is not None:
        return resp
    link = next((l for l in g.data['footer_links'] if l['id'] == link_id), None)
    if link is None:
        abort(404)
    if request.method == 'POST':
        label = request.form['label'].strip()
        url = request.form['url'].strip()
        position_raw = request.form.get('position', str(link.get('position', 0))).strip()
        try:
            position = int(position_raw) if position_raw else 0
        except ValueError:
            flash('Position must be a number.', 'error')
            return render_template('admin/footer/edit.html', link=link)
        if not label or not url:
            flash('Label and URL are required.', 'error')
            return render_template('admin/footer/edit.html', link=link)
        link.update({'label': label, 'url': url, 'position': position})
        persist_data()
        flash('Footer link updated.', 'success')
        return redirect(url_for('admin_footer'))
    return render_template('admin/footer/edit.html', link=link)


@app.route('/admin/footer/links/<int:link_id>/delete', methods=['POST'])
def admin_delete_footer_link(link_id: int):
    if (resp := require_admin()) is not None:
        return resp
    g.data['footer_links'] = [l for l in g.data['footer_links'] if l['id'] != link_id]
    persist_data()
    flash('Footer link deleted.', 'success')
    return redirect(url_for('admin_footer'))


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=int(os.environ.get('PORT', 5000)))
