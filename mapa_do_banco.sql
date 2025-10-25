accounts

id
client_id
name
type
on_budget
opening_balance
currency
created_at
updated_at
bank_id
-----------------
attachments

id	owner_type	owner_id	path	mime	size	created_at	updated_at	
-----------------
banks

id
name
slug
code
logo_svg
color_primary
color_secondary
color_bg
color_text
created_at
updated_at
------------------
budgets

id	client_id	month	category_id	planned_amount	created_at	updated_at	
------------------
cards

id
client_id
name
brand
last4
limit_amount
close_day
due_day
payment_account_id
created_at
updated_at
--------------------
categories

id
group_id
client_id
name
is_active
created_at
updated_at
--------------------
category_goals

id
client_id
category_id
month
limit_amount
created_by
created_at
updated_at
--------------------
category_groups

id	client_id	name	slug   is_active  created_at	updated_at
--------------------
clients

id
user_id
consultant_id
status
created_at
updated_at
--------------------
consultants

id
user_id
firm_name
created_at
updated_at
--------------------
failed_jobs

id	uuid	connection	queue	payload	exception	failed_at	
-----------------
goals

id	client_id	title	target_amount	due_date	priority	status	created_by	created_at	updated_at	
-----------------
goal_progress_events

id	goal_id	date	amount	note	created_at	updated_at	
-----------------
jobs

id	queue	payload	attempts	reserved_at	available_at	created_at	
-----------------
job_batches

id	name	total_jobs	pending_jobs	failed_jobs	failed_job_ids	options	cancelled_at	created_at	finished_at	
-----------------
nudges

id	task_id	channel	sent_by	sent_at	status	created_at	updated_at	
-----------------
playbooks

id	consultant_id	title	description	created_at	updated_at	
-----------------
playbook_tasks

id	playbook_id	title	description	type	frequency	custom_rrule	offset_days_from_start	default_due_hour	created_at	updated_at	
-----------------
subcategories

id
category_id
client_id
name
is_active
created_at
updated_at
--------------------
tasks

id	client_id	created_by	assigned_to	title	description	type	frequency	custom_rrule	start_at	due_at	completed_at	remind_before_minutes	status	visibility	evidence_required	related_goal_id	related_entity	created_at	updated_at	
--------------------
task_checklist_items

id	task_id	label	done	sort	created_at	updated_at	
-----------------
task_updates

id	task_id	updated_by	status_new	progress_percent	comment	evidence_file_path	created_at	updated_at	
-----------------
transactions

id
client_id
account_id
card_id
invoice_month
date
amount
installment_count
installment_index
status
type
invoice_paid
method
notes
created_at
updated_at
parent_transaction_id
------------------
transaction_categories
	
id
transaction_id
category_id
subcategory_id
created_at
updated_at
------------------
users

id
name
email
email_verified_at
password
remember_token
created_at
updated_at
role
timezone
locale
active