export const actions = {
  START_LOAD: 'START_LOAD',
  END_LOAD: 'END_LOAD',
  APPLY_DATA: 'APPLY_DATA',
  SELECT_SECTION: 'SELECT_SECTION',
  SELECT_FIRST_SECTION: 'SELECT_FIRST_SECTION',
  SET_ALL_READ: 'SET_ALL_READ',
  SET_READ: 'SET_READ',
  DISMISS_NOTIFICATION: 'DISMISS_NOTIFICATION',
  RECALC_UNREAD: 'RECALC_UNREAD',
  INCREMENT_PAGE: 'INCREMENT_PAGE',
  INCREMENT_DATA: 'INCREMENT_DATA',
  UPDATE_SECTIONS: 'UPDATE_SECTIONS',
  MERGE_DATA: 'MERGE_DATA'
}

export const types = {
  sections: {
    ALL: 'all'
  }
}

const initialState = {
  loaded: false,
  unreadCount: 0,
  sections: [],
  notifications: [],
  selectedSectionId: undefined,
  fetchPage: 1,
  fetchPerPage: 10,
  toRead: []
};

export function reducer(state = initialState, action) {
  switch (action.type) {
    case actions.UPDATE_SECTIONS:
      const newSectionsData = _formatResponseToState(action.payload);
      
      state.sections.forEach(
        (section) => {
          if (!newSectionsData.sections.map(({section}) => section).includes(section.section)) {
            newSectionsData.sections.push(section);
          }
        }
      );

      newSectionsData.sections = state.sections.map(
        section => {
          if (newSectionsData.sections.map(_section => _section.section).includes(section.section)) {
            let [ updatedSection ] = newSectionsData.sections.filter(_section => _section.section == section.section);

            section.unread_notifications_count = updatedSection.unread_notifications_count;
            section.notifications_count = updatedSection.notifications_count;

            return section;
          } else {
            return section;
          }
        }
      );

      return _merge({ sections: newSectionsData.sections, unreadCount: newSectionsData.unreadCount });

    case actions.START_LOAD:
      return _merge({ loaded: false });

    case actions.APPLY_DATA:
      return _merge(_formatResponseToState(action.payload));

    case actions.MERGE_DATA:
      return _merge(action.payload);

    case actions.INCREMENT_PAGE:
      return _merge({ fetchPage: state.fetchPage + 1 });

    case actions.INCREMENT_DATA:
      let newData = _formatResponseToState(action.payload),
        newState = Object.assign({}, state),
        notifiesIds = newState.notifications.map(n => n.notification_id),
        sectionsIds = newState.sections.map(s => s.section);

      newData.notifications.forEach(
        notification => {
          if (!notifiesIds.includes(notification.notification_id)) {
            newState.notifications.push(notification);
          }
        }
      );

      newData.sections.forEach(
        section => {
          if (!sectionsIds.includes(section.section)) {
            newState.sections.push(section);
          } else {
            newState.sections = newState.sections.map(
              _section => {
                if (section.section == _section.section) {
                  return section;
                }

                return _section;
              }
            );
          }
        }
      );

      return _merge(newState);

    case actions.END_LOAD:
      return _merge({ loaded: true });

    case actions.SELECT_SECTION:
      return _merge({
        selectedSectionId: action.payload.selectedSectionId
      });

    case actions.SELECT_FIRST_SECTION:
      let sectionId = undefined;
      state.sections.forEach(section => {
        if (section.section == types.sections.ALL || sectionId == undefined) {
          sectionId = section.section;
        }
      });
      return _merge({ selectedSectionId: sectionId });

    case actions.SET_ALL_READ:
      return _merge({
        notifications: state.notifications.map(notification => {
            notification.is_read = true;

          return notification;
        })
      });

    case actions.SET_READ:
      return _merge({
        notifications: state.notifications.map(notification => {
          if (action.payload.ids.includes(notification.notification_id)) {
            notification.is_read = true;
          }

          return notification;
        })
      });

    case actions.RECALC_UNREAD:
      return state;

    case actions.DISMISS_NOTIFICATION:
      return _merge({
        notifications: state.notifications.map(notification => {
          if (action.payload.notificationsIds.includes(notification.notification_id)) {
            notification.hidden = true;
          }

          return notification;
        })
      });

    default:
      return state;
  }

  function _merge(newData) {
    return Object.assign({}, state, newData);
  }
}

function _formatResponseToState(response) {

  if (response.notifications_center == undefined) {
    return {};
  }

  const notifications = [],
    sections = [],
    unreadCount = response.notifications_center.unread_notifications_count;

  response
    .notifications_center
    .sections
    .forEach(
      section => {
        if (section.section != types.sections.ALL && section.notifications) {
          notifications.push(...section.notifications);
        }

        sections.push(section);
      }
    );

  // sorts by pinned and timestamp
  notifications.sort(function (a, b) {
    let val_a = parseInt(a.pinned ? "" + a.timestamp + a.pinned : a.timestamp),
        val_b = parseInt(b.pinned ? "" + b.timestamp + b.pinned : b.timestamp);

    if (val_a > val_b) {
      return -1;
    }

    return 0;
  });


  return {
    notifications,
    sections,
    unreadCount
  };
}
