TheCarve is a tool for student communities to share information about classes, engage in respectful debate, and discuss ideas related to the class at a deeper level. Through strict verification and conversation structures, including debate, map, and discussion, the app aims to elicit thoughtful responses from people in disagreement or just curious.

At the center of TheCarve is its crowdsourced data system. The crowdsourcing process takes machine learning algorithms and vector reliability models I designed in bare PHP, using techniques from statistics and calculus to model uncertainty and "learn" hidden user-specific trends in reporting. This means adjusting data it displays based on sustained user biases or factoring out outliers based on adjusted MAD-based techniques.

Some review-worthy sections:

[SERVER - Official Production Server, Session Management, Co-routined App-Level Event Cycles](backend/server.php)

[MODEL - Mathematical models, low-rank matrix factorization, reliability estimation, machine larning](project/classes/Model/)

[REPOSITORY - Main Server-wide Data Processing Methods](project/classes/Repositories/ServerLobbyRepository.php)

[DATABASE - Relational Database Schema](project/database/schema.sql)
