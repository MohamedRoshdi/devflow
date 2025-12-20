#!/usr/bin/env python3
import sys

try:
    user_input = input("\n🔵 Enter your question/command (or 'stop' to exit): ")
    print(f"\n✅ You entered: {user_input}")
    
    # Save to file for reading
    with open('user_response.txt', 'w') as f:
        f.write(user_input)
except (EOFError, KeyboardInterrupt):
    print("\n❌ Interactive input not available in this environment")
    print("💡 Please type your question directly in the chat")
    sys.exit(1)