import os
import sys


def is_directory_empty(path):
    """Return True if the directory exists and contains nothing."""
    return os.path.isdir(path) and not any(os.scandir(path))


def get_tree_level(line):
    """
    Determine nesting depth from common tree output such as:

    project/
    ├── app/
    │   ├── index.php
    │   └── config.php
    └── README.md
    """

    prefix = ""
    position = 0

    while position < len(line):
        chunk = line[position:position + 4]

        if chunk in ("│   ", "    "):
            prefix += chunk
            position += 4
        else:
            break

    return len(prefix) // 4


def clean_tree_name(line):
    """Remove tree-drawing characters and return the actual item name."""
    line = line.strip()

    for marker in ("├── ", "└── ", "├──", "└──"):
        if line.startswith(marker):
            line = line[len(marker):]

    return line.strip()


def looks_like_file(name):
    """
    Determine whether an entry appears to be a file.

    Examples:
        index.php       -> file
        README.md       -> file
        .htaccess       -> file
        package.json    -> file
        src/            -> directory
    """

    if name.endswith("/") or name.endswith("\\"):
        return False

    basename = os.path.basename(name)

    # Dotfiles such as .htaccess, .gitignore, .env
    if basename.startswith(".") and len(basename) > 1:
        return True

    # Regular extension-based files
    return "." in basename


def create_tree_from_file(tree_file_path, target_path):
    tree_file_path = os.path.abspath(tree_file_path)
    target_path = os.path.abspath(target_path)

    # ------------------------------------------------------------
    # Validate source file
    # ------------------------------------------------------------
    if not os.path.isfile(tree_file_path):
        print(f"ERROR: Source file not found:")
        print(f"  {tree_file_path}")
        sys.exit(1)

    # ------------------------------------------------------------
    # Create target directory if it does not exist
    # ------------------------------------------------------------
    if not os.path.exists(target_path):
        try:
            os.makedirs(target_path)
            print(f"Created target directory:")
            print(f"  {target_path}")
            print()
        except OSError as exc:
            print(f"ERROR: Could not create target directory:")
            print(f"  {target_path}")
            print(f"Reason: {exc}")
            sys.exit(1)

    # ------------------------------------------------------------
    # Make sure target is actually a directory
    # ------------------------------------------------------------
    if not os.path.isdir(target_path):
        print(f"ERROR: Target path is not a directory:")
        print(f"  {target_path}")
        sys.exit(1)

    # ------------------------------------------------------------
    # Refuse to run if directory contains anything
    # ------------------------------------------------------------
    if not is_directory_empty(target_path):
        print("ERROR: Target directory must be empty.")
        print(f"Target:")
        print(f"  {target_path}")
        print()
        print("No files or folders were created.")
        sys.exit(1)

    print(f"Source:")
    print(f"  {tree_file_path}")
    print()

    print(f"Target:")
    print(f"  {target_path}")
    print()

    print("Creating structure...")
    print("-" * 60)

    # Stores directories by tree depth.
    #
    # Example:
    # directory_stack[0] = target directory
    # directory_stack[1] = first nested directory
    # directory_stack[2] = directory nested under that
    directory_stack = [target_path]

    with open(tree_file_path, "r", encoding="utf-8") as tree_file:
        for raw_line in tree_file:
            line = raw_line.rstrip("\r\n")

            if not line.strip():
                continue

            level = get_tree_level(line)
            name = clean_tree_name(line)

            if not name:
                continue

            # Remove trailing directory markers
            cleaned_name = name.rstrip("/\\")

            # ----------------------------------------------------
            # Determine parent directory
            # ----------------------------------------------------
            if level >= len(directory_stack):
                parent_directory = directory_stack[-1]
            else:
                parent_directory = directory_stack[level]

            item_path = os.path.join(parent_directory, cleaned_name)

            # ----------------------------------------------------
            # Create file
            # ----------------------------------------------------
            if looks_like_file(name):
                os.makedirs(parent_directory, exist_ok=True)

                with open(item_path, "w", encoding="utf-8"):
                    pass

                print(f"[FILE] {item_path}")

            # ----------------------------------------------------
            # Create directory
            # ----------------------------------------------------
            else:
                os.makedirs(item_path, exist_ok=True)

                # Remove deeper paths from previous branches
                directory_stack = directory_stack[:level + 1]

                # Store this directory as the parent for next level
                directory_stack.append(item_path)

                print(f"[DIR ] {item_path}")

    print("-" * 60)
    print("Structure created successfully.")


def print_usage():
    script_name = os.path.basename(sys.argv[0])

    print("Usage:")
    print(f'  python {script_name} <source_file> <target_path>')
    print()
    print("Example:")
    print(
        f'  python {script_name} structure.txt '
        f'"B:\\htdocs\\createdFolder"'
    )


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print_usage()
        sys.exit(1)

    source_file = sys.argv[1]
    target_path = sys.argv[2]

    create_tree_from_file(source_file, target_path)
