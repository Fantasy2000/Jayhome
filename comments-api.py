#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
评论弹幕API服务器
提供评论的保存和获取功能
使用方法：python comments-api.py
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import json
import os
from datetime import datetime
from pathlib import Path

app = Flask(__name__)
CORS(app)  # 允许跨域请求

# 评论数据文件路径
COMMENTS_FILE = Path('comments.json')

def load_comments():
    """加载评论数据"""
    if COMMENTS_FILE.exists():
        try:
            with open(COMMENTS_FILE, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            print(f"加载评论失败: {e}")
            return []
    return []

def save_comments(comments):
    """保存评论数据"""
    try:
        with open(COMMENTS_FILE, 'w', encoding='utf-8') as f:
            json.dump(comments, f, ensure_ascii=False, indent=2)
        return True
    except Exception as e:
        print(f"保存评论失败: {e}")
        return False

@app.route('/api/comments', methods=['GET'])
def get_comments():
    """获取所有评论"""
    comments = load_comments()
    return jsonify({
        'success': True,
        'data': comments,
        'count': len(comments)
    })

@app.route('/api/comments', methods=['POST'])
def add_comment():
    """添加新评论"""
    try:
        data = request.get_json()
        text = data.get('text', '').strip()
        
        if not text:
            return jsonify({
                'success': False,
                'message': '评论内容不能为空'
            }), 400
        
        if len(text) > 50:
            return jsonify({
                'success': False,
                'message': '评论内容不能超过50个字符'
            }), 400
        
        # 加载现有评论
        comments = load_comments()
        
        # 创建新评论
        new_comment = {
            'id': int(datetime.now().timestamp() * 1000),
            'text': text,
            'timestamp': datetime.now().isoformat()
        }
        
        # 添加到列表
        comments.append(new_comment)
        
        # 限制评论数量（最多保存1000条）
        if len(comments) > 1000:
            comments = comments[-1000:]
        
        # 保存评论
        if save_comments(comments):
            return jsonify({
                'success': True,
                'data': new_comment,
                'message': '评论添加成功'
            })
        else:
            return jsonify({
                'success': False,
                'message': '保存评论失败'
            }), 500
            
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'服务器错误: {str(e)}'
        }), 500

@app.route('/api/comments/<int:comment_id>', methods=['DELETE'])
def delete_comment(comment_id):
    """删除评论（可选功能）"""
    comments = load_comments()
    comments = [c for c in comments if c.get('id') != comment_id]
    
    if save_comments(comments):
        return jsonify({
            'success': True,
            'message': '评论删除成功'
        })
    else:
        return jsonify({
            'success': False,
            'message': '删除评论失败'
        }), 500

@app.route('/api/health', methods=['GET'])
def health():
    """健康检查"""
    return jsonify({
        'success': True,
        'message': 'API服务正常运行'
    })

if __name__ == '__main__':
    print("=" * 60)
    print("🚀 评论弹幕API服务器启动")
    print("=" * 60)
    print("📡 API地址:")
    print("   GET  /api/comments  - 获取所有评论")
    print("   POST /api/comments  - 添加新评论")
    print("   DELETE /api/comments/<id> - 删除评论")
    print("=" * 60)
    print("💡 提示: 按 Ctrl+C 停止服务器")
    print("=" * 60 + "\n")
    
    app.run(host='0.0.0.0', port=5000, debug=True)


