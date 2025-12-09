#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
简单的开发服务器，支持自动刷新
使用方法：python dev-server.py
"""

import http.server
import socketserver
import webbrowser
import os
import sys
import socket
from pathlib import Path

# 设置端口
PORT = 8000

# 获取当前脚本所在目录（使用绝对路径，避免中文路径问题）
try:
    if getattr(sys, 'frozen', False):
        BASE_DIR = Path(sys.executable).parent
    else:
        BASE_DIR = Path(__file__).parent.resolve()
except:
    BASE_DIR = Path(os.getcwd()).resolve()

def check_port(port):
    """检查端口是否被占用"""
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    result = sock.connect_ex(('127.0.0.1', port))
    sock.close()
    return result == 0

class MyHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        # 添加CORS头，允许跨域
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
        self.send_header('Pragma', 'no-cache')
        self.send_header('Expires', '0')
        super().end_headers()
    
    def log_message(self, format, *args):
        # 自定义日志格式
        print(f"[{self.log_date_time_string()}] {format % args}")

def main():
    # 检查端口是否被占用
    if check_port(PORT):
        print(f"⚠️  警告: 端口 {PORT} 已被占用！")
        print(f"💡 尝试使用其他端口...")
        # 尝试其他端口
        for alt_port in [8001, 8002, 8080, 3000, 5000]:
            if not check_port(alt_port):
                global PORT
                PORT = alt_port
                print(f"✅ 使用端口 {PORT}")
                break
        else:
            print("❌ 错误: 所有常用端口都被占用，请手动关闭占用端口的程序")
            input("按回车键退出...")
            sys.exit(1)
    
    # 切换到项目目录
    try:
        os.chdir(str(BASE_DIR))
        print(f"📂 工作目录: {os.getcwd()}")
    except Exception as e:
        print(f"❌ 错误: 无法切换到项目目录: {e}")
        input("按回车键退出...")
        sys.exit(1)
    
    # 检查index.html是否存在
    if not os.path.exists('index.html'):
        print("⚠️  警告: 未找到 index.html 文件")
        print(f"📂 当前目录: {os.getcwd()}")
        print("💡 请确保在正确的目录中运行此脚本")
    
    # 创建服务器
    try:
        httpd = socketserver.TCPServer(("", PORT), MyHTTPRequestHandler)
        httpd.allow_reuse_address = True
        
        url = f"http://localhost:{PORT}"
        print("\n" + "=" * 60)
        print(f"🚀 开发服务器已启动！")
        print(f"📂 服务目录: {os.getcwd()}")
        print(f"🌐 访问地址: {url}")
        print(f"📄 主页: {url}/index.html")
        print("=" * 60)
        print("💡 提示: 修改代码后刷新浏览器即可看到效果")
        print("⚠️  按 Ctrl+C 停止服务器")
        print("=" * 60 + "\n")
        
        # 自动打开浏览器
        try:
            webbrowser.open(url)
            print("🌐 已尝试自动打开浏览器...")
        except Exception as e:
            print(f"⚠️  无法自动打开浏览器: {e}")
            print(f"💡 请手动访问: {url}")
        
        # 启动服务器
        print("🔄 服务器运行中...\n")
        httpd.serve_forever()
        
    except OSError as e:
        if "Address already in use" in str(e) or "地址已在使用" in str(e):
            print(f"❌ 错误: 端口 {PORT} 已被占用")
            print("💡 解决方案:")
            print(f"   1. 关闭占用端口 {PORT} 的程序")
            print(f"   2. 或修改脚本中的 PORT 变量为其他端口")
        else:
            print(f"❌ 错误: {e}")
        input("按回车键退出...")
        sys.exit(1)
    except KeyboardInterrupt:
        print("\n\n🛑 服务器已停止")
        sys.exit(0)
    except Exception as e:
        print(f"❌ 发生错误: {e}")
        import traceback
        traceback.print_exc()
        input("按回车键退出...")
        sys.exit(1)

if __name__ == "__main__":
    main()


