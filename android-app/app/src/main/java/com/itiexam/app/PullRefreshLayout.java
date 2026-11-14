package com.itiexam.app;

import android.animation.ValueAnimator;
import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.RectF;
import android.util.AttributeSet;
import android.view.MotionEvent;
import android.view.View;
import android.view.ViewConfiguration;
import android.view.animation.DecelerateInterpolator;
import android.widget.FrameLayout;

/**
 * Pull down at the top of a page to reload it. A round white badge with a brand-coloured arc follows the finger
 * and spins while the page reloads. Turned off on test pages, so a test is never lost by accident.
 */
public class PullRefreshLayout extends FrameLayout {
    public interface Listener { void onRefresh(); }

    private final float density = getResources().getDisplayMetrics().density;
    private final int touchSlop = ViewConfiguration.get(getContext()).getScaledTouchSlop();
    private final float trigger = 96 * density, maxPull = 150 * density, size = 42 * density;
    private final Paint circle = new Paint(Paint.ANTI_ALIAS_FLAG), shadow = new Paint(Paint.ANTI_ALIAS_FLAG), arc = new Paint(Paint.ANTI_ALIAS_FLAG);
    private final RectF oval = new RectF();
    private View target;
    private Listener listener;
    private boolean enabled = true, dragging, refreshing;
    private float downX, downY, pull, spin;
    private ValueAnimator spinner, settle;

    public PullRefreshLayout(Context c, AttributeSet a) {
        super(c, a);
        setWillNotDraw(false);
        circle.setColor(0xFFFFFFFF);
        shadow.setColor(0x26000000);                   // drawn by hand: a shadow layer would force software rendering of the page
        arc.setStyle(Paint.Style.STROKE);
        arc.setStrokeWidth(3 * density);
        arc.setStrokeCap(Paint.Cap.ROUND);
    }

    public void setListener(Listener l) { listener = l; }

    public void setPullEnabled(boolean on) { enabled = on; }

    public void setRefreshing(boolean on) {
        if (refreshing == on) return;
        refreshing = on;
        if (on) {
            animatePull(trigger * 0.8f);
            spinner = ValueAnimator.ofFloat(0, 360);
            spinner.setDuration(800);
            spinner.setRepeatCount(ValueAnimator.INFINITE);
            spinner.addUpdateListener(new ValueAnimator.AnimatorUpdateListener() {
                @Override public void onAnimationUpdate(ValueAnimator va) { spin = (float) va.getAnimatedValue(); invalidate(); }
            });
            spinner.start();
        } else {
            if (spinner != null) spinner.cancel();
            animatePull(0);
        }
    }

    private View target() {
        if (target == null && getChildCount() > 0) target = getChildAt(0);
        return target;
    }

    private boolean atTop() { View t = target(); return t != null && !t.canScrollVertically(-1); }

    @Override
    public boolean onInterceptTouchEvent(MotionEvent e) {
        if (!enabled || refreshing) return false;
        switch (e.getActionMasked()) {
            case MotionEvent.ACTION_DOWN:
                downX = e.getX(); downY = e.getY(); dragging = false;
                break;
            case MotionEvent.ACTION_MOVE:
                float dy = e.getY() - downY, dx = Math.abs(e.getX() - downX);
                if (!dragging && dy > touchSlop && dy > dx * 1.5f && atTop()) { dragging = true; downY = e.getY(); }
                break;
            default:
                dragging = false;
        }
        return dragging;
    }

    @Override
    public boolean onTouchEvent(MotionEvent e) {
        if (!dragging) return super.onTouchEvent(e);
        switch (e.getActionMasked()) {
            case MotionEvent.ACTION_MOVE:
                float dy = Math.max(0, e.getY() - downY);
                pull = Math.min(maxPull, dy * 0.55f);          // resistance
                spin = pull / trigger * 270f;
                invalidate();
                return true;
            case MotionEvent.ACTION_UP:
            case MotionEvent.ACTION_CANCEL:
                dragging = false;
                if (pull >= trigger && listener != null) {
                    performHapticFeedback(android.view.HapticFeedbackConstants.VIRTUAL_KEY);
                    setRefreshing(true);
                    listener.onRefresh();
                } else animatePull(0);
                return true;
        }
        return true;
    }

    private void animatePull(float to) {
        if (settle != null) settle.cancel();
        settle = ValueAnimator.ofFloat(pull, to);
        settle.setDuration(220);
        settle.setInterpolator(new DecelerateInterpolator());
        settle.addUpdateListener(new ValueAnimator.AnimatorUpdateListener() {
            @Override public void onAnimationUpdate(ValueAnimator va) { pull = (float) va.getAnimatedValue(); invalidate(); }
        });
        settle.start();
    }

    @Override
    protected void dispatchDraw(Canvas c) {
        super.dispatchDraw(c);
        if (pull <= 1) return;
        float cx = getWidth() / 2f, cy = pull - size / 2f, r = size / 2f;
        float p = Math.min(1f, pull / trigger);
        c.save();
        c.drawCircle(cx, cy + 1.5f * density, r + 1.2f * density, shadow);
        c.drawCircle(cx, cy, r, circle);
        oval.set(cx - r * 0.55f, cy - r * 0.55f, cx + r * 0.55f, cy + r * 0.55f);
        arc.setColor(p >= 1f || refreshing ? 0xFFF7740F : 0xFF1F4FD6);
        arc.setAlpha((int) (120 + 135 * p));
        c.drawArc(oval, spin - 90, refreshing ? 270 : 300 * p, false, arc);
        c.restore();
    }
}
