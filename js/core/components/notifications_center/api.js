export async function getNotifications(params) {
  return await _promisedAjax({
    reqType: `request`,
    url: window.fn_url(`notifications_center.manage`),
    reqParams: {
      data: params,
      hidden: true,
      method: `get`,
    }
  });
}

export async function setNotificationsRead(notificationIds) {
  const notification_ids = notificationIds;

  return await _promisedAjax({
    reqType: `request`,
    url: window.fn_url(`notifications_center.set_read`),
    reqParams: {
      data: { notification_ids },
      hidden: true,
      method: `post`
    }
  });
}

export async function setAllNotificationsRead() {

  return await _promisedAjax({
    reqType: `request`,
    url: window.fn_url(`notifications_center.set_all_as_read`),
    reqParams: {
      hidden: true,
      method: `post`
    }
  });
}

export async function dismissNotifications(notificationIds) {
  const notification_ids = notificationIds;

  return await _promisedAjax({
    reqType: `request`,
    url: window.fn_url(`notifications_center.dismiss`),
    reqParams: {
      data: { notification_ids },
      hidden: true,
      method: `post`
    }
  });
}

function _promisedAjax({ reqType, url, reqParams }) {
  return new Promise(
    (resolve, reject) => {

      reqParams.callback = (res) => {
        resolve(res);
      }

      reqParams.error = (err) => {
        reject(err);
      }

      $.ceAjax(reqType, url, reqParams);
    }
  );
}