-- Real course learning content for UnivLearn
-- Import this after schema/seed data to replace placeholder course materials.

ALTER TABLE course_contents MODIFY ContentURL TEXT;

DELETE FROM course_contents
WHERE ContentURL LIKE '%dQw4w9WgXcQ%'
   OR ContentURL LIKE '%Refer to the textbook Chapter 1.%'
   OR ContentURL LIKE '%youtube.com/watch?v=example%'
   OR ContentURL LIKE '%example.com/%'
   OR ContentURL LIKE '%docs.example.com/%';

INSERT INTO course_contents (CourseID, ContentType, ContentTitle, ContentURL) VALUES
(1, 'Text', 'Python Variables and Control Flow', 'Python programs are built from small instructions. Variables store values, conditionals choose between paths, and loops repeat useful work. Practice by creating a list of scores, looping through the list, and counting how many scores are above 70. This teaches the flow used in automation, data analysis, and backend development.'),
(1, 'Text', 'Python Functions and Modules', 'A function packages a repeatable idea. Input goes in, useful output comes out. Write functions such as calculate_average(scores), format_username(name), and is_passing(score). Modules help split larger programs into files so code can be tested and reused.'),
(1, 'Text', 'Python Data Practice', 'Most useful Python programs read, clean, and summarize data. Practice opening a small CSV file, converting string values to numbers, handling missing values, and printing totals. The goal is to turn messy information into clear decisions.'),

(2, 'Text', 'HTML, CSS, and Page Structure', 'HTML gives content meaning through headings, paragraphs, forms, buttons, and navigation. CSS controls spacing, layout, color, and responsiveness. A strong web page starts with semantic HTML, then uses flexbox or grid to work on phones and desktops.'),
(2, 'Text', 'JavaScript Interactivity', 'JavaScript makes pages respond to users. Use event listeners for clicks and forms, validate input before submission, and update the DOM with clear feedback. Good interactivity is predictable: users should know what changed and why.'),
(2, 'Text', 'Backend Routes and APIs', 'A backend route receives a request, validates input, talks to the database, and returns a response. Practice routes for login, listing courses, creating content, and showing student progress. This is how a web app becomes dynamic.'),

(3, 'Text', 'Relational Database Design', 'A relational database stores related facts in tables. Users, courses, enrollments, quizzes, and results should be separate tables connected by IDs. Good design avoids duplicate data and makes questions easy to answer.'),
(3, 'Text', 'SQL Queries That Matter', 'SQL is how you ask the database questions. SELECT retrieves rows, JOIN combines related tables, WHERE filters, GROUP BY summarizes, and ORDER BY sorts. Practice counting enrollments per course and calculating average quiz scores.'),
(3, 'Text', 'Indexes and Data Integrity', 'Indexes speed up lookups on common columns such as UserID and CourseID. Foreign keys protect relationships so an enrollment cannot point to a course that does not exist. Constraints help the database reject bad data.'),

(4, 'Text', 'Big O and Efficiency', 'Algorithms are judged by how they grow as input grows. A loop through n items is O(n), nested loops are often O(n squared), and binary search is O(log n). This helps predict if a solution will still work with large data.'),
(4, 'Text', 'Core Data Structures', 'Arrays store ordered lists, hash maps support fast lookup, stacks handle last-in-first-out tasks, and queues handle first-in-first-out tasks. Choosing the right structure often matters more than clever code.'),
(4, 'Text', 'Problem Solving Pattern', 'Read the problem, write examples, identify inputs and outputs, choose a data structure, then code the simplest correct version. Test empty input, one item, duplicates, and very large values.'),

(5, 'Text', 'Cloud Service Models', 'Cloud platforms provide infrastructure without buying physical servers. IaaS gives virtual machines, PaaS gives managed app hosting, and SaaS gives complete software. Learn which responsibility stays with the developer.'),
(5, 'Text', 'Scalability and Reliability', 'Reliable cloud systems use load balancing, health checks, backups, and monitoring. Scaling means adding capacity as traffic grows. Good architecture expects failure and reduces its impact.'),
(5, 'Text', 'Cloud Security Basics', 'Cloud security starts with least privilege. Give users and services only the permissions they need. Protect secrets, use HTTPS, keep logs, and separate public resources from private databases.'),

(6, 'Text', 'Data, Features, and Labels', 'Machine learning starts with examples. Features are inputs, labels are answers, and the model learns patterns between them. Predicting house prices might use size, location, rooms, and age as features.'),
(6, 'Text', 'Training and Evaluation', 'Training fits a model to data. Evaluation checks whether it generalizes. Split data into training and test sets, measure accuracy or error, and watch for overfitting.'),
(6, 'Text', 'Responsible AI', 'Models can reflect bias in their training data. Responsible AI means checking performance across groups, explaining limitations, protecting user data, and using human review for high-impact decisions.'),

(7, 'Text', 'Threats and Attack Surfaces', 'Cyber defense begins by identifying what can be attacked: accounts, networks, servers, databases, forms, and APIs. Common threats include phishing, weak passwords, injection attacks, malware, and unpatched software.'),
(7, 'Text', 'Defense in Depth', 'No single control is enough. Use strong authentication, input validation, firewalls, least privilege, backups, logging, and monitoring together. If one layer fails, another should reduce damage.'),
(7, 'Text', 'Incident Response', 'When something goes wrong, respond in order: detect, contain, investigate, remove the threat, recover systems, and document lessons learned. Speed matters, but clear records matter too.'),

(8, 'Text', 'Flutter Widgets and Layout', 'Flutter apps are built from widgets. Text, images, rows, columns, buttons, and screens are all widgets. Layout comes from composing small widgets into interfaces that adapt to screen sizes.'),
(8, 'Text', 'State and Navigation', 'State is data that changes, such as selected tabs, form values, or loaded courses. Navigation moves users between screens. A good mobile app keeps state predictable.'),
(8, 'Text', 'Connecting Flutter to APIs', 'Real apps fetch and send data. Use HTTP requests to load courses, submit quiz answers, and update profiles. Always handle loading, success, empty, and error states.'),

(9, 'Text', 'Continuous Integration', 'CI runs checks automatically when code changes. A pipeline can install dependencies, run tests, lint code, and report failures before broken code reaches users.'),
(9, 'Text', 'Containers and Deployment', 'Containers package an app with its runtime so it behaves consistently. Docker images can be deployed to servers or cloud platforms, making releases repeatable.'),
(9, 'Text', 'Monitoring and Logs', 'After deployment, teams need to know if the app is healthy. Logs explain what happened, metrics show trends, and alerts warn when users may be affected.'),

(10, 'Text', 'User Research and Goals', 'UI/UX starts by understanding users. Identify what they need to do, what frustrates them, and what success looks like. Design decisions should support real tasks.'),
(10, 'Text', 'Layout, Hierarchy, and Contrast', 'Visual hierarchy tells users what matters first. Use spacing, size, contrast, and grouping to guide attention. Good layouts make scanning easy and reduce confusion.'),
(10, 'Text', 'Prototyping and Testing', 'A prototype lets users try a flow before full development. Test whether they can complete tasks, watch where they hesitate, and improve based on evidence.'),

(11, 'Text', 'Object-Oriented Programming in Java', 'Java organizes code with classes and objects. Classes define structure and behavior; objects are real instances. Encapsulation protects data, and interfaces define contracts.'),
(11, 'Text', 'Enterprise Application Layers', 'Enterprise Java apps often separate controllers, services, repositories, and models. This keeps business rules away from database details and makes large systems easier to test.'),
(11, 'Text', 'Transactions and Reliability', 'Enterprise systems must handle failures carefully. Transactions keep related database changes together so money, grades, orders, or enrollments do not end up half-saved.'),

(12, 'Text', 'Network Foundations', 'Networks move data between devices using protocols. IP addresses identify devices, DNS turns names into addresses, and TCP helps deliver data reliably.'),
(12, 'Text', 'Routing and Switching', 'Switches move traffic inside a local network; routers move traffic between networks. Understanding both helps diagnose why a device can or cannot reach a service.'),
(12, 'Text', 'Network Security Controls', 'Firewalls, VLANs, VPNs, access control lists, and monitoring tools reduce risk. Security is strongest when network design limits unnecessary access.'),

(13, 'Text', 'Ledgers and Blocks', 'A blockchain is a shared ledger where transactions are grouped into blocks. Each block references the previous one, making history difficult to alter without detection.'),
(13, 'Text', 'Consensus', 'Consensus is how participants agree on the valid state of the ledger. Different systems use different methods with tradeoffs in speed, energy, and decentralization.'),
(13, 'Text', 'Smart Contracts', 'Smart contracts are programs stored on a blockchain. They can manage tokens, voting, payments, or agreements, but bugs can be expensive because deployed code is hard to change.'),

(14, 'Text', 'Scenes, GameObjects, and Components', 'Unity games are built from scenes containing GameObjects. Components add behavior, physics, rendering, audio, or scripts. This model builds complex behavior from small parts.'),
(14, 'Text', 'Game Loop and Input', 'Games repeatedly read input, update state, and render frames. Students should connect keyboard, mouse, or touch input to player movement and game actions.'),
(14, 'Text', 'Physics and Feedback', 'Physics handles collisions, gravity, and forces. Feedback such as sound, animation, particles, and score changes helps players understand the result of actions.'),

(15, 'Text', 'Processes and Threads', 'An operating system runs programs as processes. Threads allow work inside a process to happen concurrently. Scheduling decides which work gets CPU time.'),
(15, 'Text', 'Memory Management', 'Memory is divided and protected so programs do not overwrite each other. Stack, heap, paging, and virtual memory explain how large programs run safely.'),
(15, 'Text', 'Files and System Calls', 'Programs ask the OS to open files, read input, allocate memory, and use devices through system calls. This boundary protects the machine while giving programs useful services.');

INSERT INTO course_contents (CourseID, ContentType, ContentTitle, ContentURL) VALUES
(1, 'PDF', 'Python Mastery Handout', 'public/course_pdf.php?title=Python%20Mastery%20Handout&body=Variables%2C%20functions%2C%20modules%2C%20and%20data%20practice%20are%20the%20foundation%20of%20Python%20learning.'),
(2, 'PDF', 'Modern Web Development Handout', 'public/course_pdf.php?title=Modern%20Web%20Development%20Handout&body=HTML%20structures%20content%2C%20CSS%20styles%20layouts%2C%20JavaScript%20adds%20interaction%2C%20and%20backend%20routes%20connect%20data.'),
(3, 'PDF', 'Database Pro Handout', 'public/course_pdf.php?title=Database%20Pro%20Handout&body=Relational%20design%2C%20SQL%20queries%2C%20indexes%2C%20and%20foreign%20keys%20help%20turn%20stored%20data%20into%20reliable%20information.'),
(4, 'PDF', 'Algorithms Handout', 'public/course_pdf.php?title=Algorithms%20Handout&body=Big%20O%2C%20arrays%2C%20hash%20maps%2C%20stacks%2C%20queues%2C%20and%20testing%20edge%20cases%20are%20core%20algorithm%20skills.'),
(5, 'PDF', 'Cloud Architecture Handout', 'public/course_pdf.php?title=Cloud%20Architecture%20Handout&body=Cloud%20systems%20use%20service%20models%2C%20scaling%2C%20monitoring%2C%20backups%2C%20least%20privilege%2C%20and%20secure%20networking.'),
(6, 'PDF', 'AI and Machine Learning Handout', 'public/course_pdf.php?title=AI%20and%20Machine%20Learning%20Handout&body=Machine%20learning%20uses%20features%2C%20labels%2C%20training%2C%20evaluation%2C%20and%20responsible%20checks%20to%20build%20models.'),
(7, 'PDF', 'Cyber Defense Handout', 'public/course_pdf.php?title=Cyber%20Defense%20Handout&body=Threat%20modeling%2C%20defense%20in%20depth%2C%20least%20privilege%2C%20logging%2C%20monitoring%2C%20and%20incident%20response%20protect%20systems.'),
(8, 'PDF', 'Flutter App Development Handout', 'public/course_pdf.php?title=Flutter%20App%20Development%20Handout&body=Flutter%20apps%20use%20widgets%2C%20layout%2C%20state%2C%20navigation%2C%20API%20calls%2C%20and%20clear%20loading%20or%20error%20states.'),
(9, 'PDF', 'DevOps Engineering Handout', 'public/course_pdf.php?title=DevOps%20Engineering%20Handout&body=CI%20pipelines%2C%20containers%2C%20deployment%2C%20monitoring%2C%20logs%2C%20and%20alerts%20help%20teams%20ship%20reliable%20software.'),
(10, 'PDF', 'Digital Design UI UX Handout', 'public/course_pdf.php?title=Digital%20Design%20UI%20UX%20Handout&body=User%20research%2C%20visual%20hierarchy%2C%20contrast%2C%20prototyping%2C%20and%20testing%20make%20interfaces%20usable.'),
(11, 'PDF', 'Java Enterprise Handout', 'public/course_pdf.php?title=Java%20Enterprise%20Handout&body=Java%20enterprise%20systems%20use%20objects%2C%20interfaces%2C%20layers%2C%20repositories%2C%20services%2C%20and%20transactions.'),
(12, 'PDF', 'Network Security Handout', 'public/course_pdf.php?title=Network%20Security%20Handout&body=Networks%20depend%20on%20IP%2C%20DNS%2C%20TCP%2C%20switching%2C%20routing%2C%20firewalls%2C%20VLANs%2C%20VPNs%2C%20and%20monitoring.'),
(13, 'PDF', 'Blockchain Foundations Handout', 'public/course_pdf.php?title=Blockchain%20Foundations%20Handout&body=Blockchains%20use%20shared%20ledgers%2C%20blocks%2C%20consensus%2C%20and%20smart%20contracts%20to%20coordinate%20trusted%20state.'),
(14, 'PDF', 'Unity Game Engine Handout', 'public/course_pdf.php?title=Unity%20Game%20Engine%20Handout&body=Unity%20uses%20scenes%2C%20GameObjects%2C%20components%2C%20scripts%2C%20input%2C%20physics%2C%20and%20feedback%20to%20build%20games.'),
(15, 'PDF', 'OS Internals Handout', 'public/course_pdf.php?title=OS%20Internals%20Handout&body=Operating%20systems%20manage%20processes%2C%20threads%2C%20scheduling%2C%20memory%2C%20files%2C%20devices%2C%20and%20system%20calls.');
