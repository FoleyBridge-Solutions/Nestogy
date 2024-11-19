#!/bin/bash

# Create or clear the packages.txt file
> packages.txt

echo "Scanning for PHP packages..."

# Get Composer packages if composer.json exists
if [ -f "composer.json" ]; then
    echo "# Composer Dependencies" >> packages.txt
    echo "----------------" >> packages.txt
    composer show --format=text | grep -E '^[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+\s+[0-9]+' >> packages.txt
    echo -e "\n" >> packages.txt
fi

# Get system PHP packages
echo "# System PHP Packages" >> packages.txt
echo "----------------" >> packages.txt
dpkg -l | grep -E "^ii.*php" >> packages.txt
echo -e "\n" >> packages.txt

# Get Node packages if package.json exists
if [ -f "package.json" ]; then
    echo "# Node.js Dependencies" >> packages.txt
    echo "----------------" >> packages.txt
    npm list --depth=0 >> packages.txt
    echo -e "\n" >> packages.txt
fi

# Get Python packages if requirements.txt exists
if [ -f "requirements.txt" ]; then
    echo "# Python Dependencies" >> packages.txt
    echo "----------------" >> packages.txt
    pip freeze >> packages.txt
    echo -e "\n" >> packages.txt
fi

echo "Package list has been updated in packages.txt" 