#coding:utf-8

# 发送信的最小时间单位粒度, 比如最小粒度是天, 那么系统每隔一天扫描一次未发送的信, 
# 到了送信时间就送出去
# 单位: 秒
MINIMUM_TIME_GRANULARITY = 10

DB_config = {
    "host": "127.0.0.1",
    "user": "root",
    "passwd": "",
    "db": "manji",
    "charset": "utf8"
}

DB_attri = {
    "table_name": ["t_mail", "t_mail_state"],
    "t_mail": ["mail_id", "user_id", "address_id", "pub_time", "is_read", "arrive_time", "mail_content", "email"],
    "t_mail_state": [ "mstate_id", "poster_id", "start_time", "end_time", "description", "mood", "mood_time", "mail_id"],
}

# 邮件服务器设置
smtp_server = 'smtp.qq.com'
smtp_username = '452570607@qq.com'
smtp_password = ''

# 标准邮件标题
standard_email_subject = '您的好友通过yoyou给你发了一封超用心的信'
