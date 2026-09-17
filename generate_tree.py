import os
import sys

def create_tree_from_file(tree_file_path):
    if not os.path.exists(tree_file_path):
        print(f"Error: File '{tree_file_path}' not found.")
        return

    # Track the active path at each depth level
    # Level 0 is the current working directory
    path_stack = ["."]

    with open(tree_file_path, "r", encoding="utf-8") as f:
        for line in f:
            # Clean up the line while preserving structural whitespace
            stripped = line.rstrip()
            if not stripped:
                continue

            # 1. Determine depth based on tree characters or double-space indents
            # Count standard tree characters or clean indentations
            prefix_chars = stripped.replace("├──", "  ").replace("└──", "  ").replace("│", " ")
            indent_count = len(prefix_chars) - len(prefix_chars.lstrip())

            # Divide by standard 2 or 4 space indents to find the numerical depth level
            # We assume a standard 2 or 4 space indentation system
            level = (indent_count // 2) + 1

            # 2. Extract the clean file or folder name
            name = stripped.strip()
            for char in ["├──", "└──", "│", "─"]:
                name = name.replace(char, "")
            name = name.strip()

            if not name:
                continue

            # 3. Adjust the path stack to match the current item's depth level
            path_stack = path_stack[:level]

            # 4. Construct the full path
            current_parent = os.path.join(*path_stack)
            target_path = os.path.join(current_parent, name)

            # 5. Differentiate folders from files and create them
            # Trailing slashes or lack of extension usually implies a folder.
            # Modify logic if your tree uses specific file rules.
            if name.endswith("/") or "." not in name:
                os.makedirs(target_path, exist_ok=True)
                path_stack.append(name)  # Push folder to stack for nested items
                print(f"📁 Created Directory: {target_path}")
            else:
                # Ensure the parent directory exists first
                os.makedirs(current_parent, exist_ok=True)
                with open(target_path, "w", encoding="utf-8") as file:
                    pass  # Create empty file
                print(f"📄 Created File:      {target_path}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python generate_tree.py <path_to_tree_txt_file>")
    else:
        create_tree_from_file(sys.argv[1])
