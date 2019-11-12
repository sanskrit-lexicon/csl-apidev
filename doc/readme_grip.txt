
A python tool was helpful in developing and debugging the collection of
'.md' (Github markdown) files in this directory.
grip repository:  https://github.com/joeyespo/grip

pip install grip

From a terminal (e.g., Git Bash)
- cd to this directory
- make a first .md file, e.g. touch apidev.md
- start grip server from a command line:  grip apidev.md
  You will see a grip url: e.g. 'http://localhost:6419'
- Open a browser, and set the address to the grip url.

Now develop the application with .md files. grip server will
hotload saved files, so you can see a close approximation to
how the .md documentation will appear when loaded to GitHub.

